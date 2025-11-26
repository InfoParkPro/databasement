<?php

namespace App\Services\Backup;

use App\Models\DatabaseServer;
use App\Models\Snapshot;
use App\Services\Backup\Databases\MysqlDatabase;
use App\Services\Backup\Databases\PostgresqlDatabase;
use App\Services\Backup\Filesystems\FilesystemProvider;
use App\Services\DatabaseConnectionTester;
use PDO;
use PDOException;
use Symfony\Component\Process\Process;

class RestoreTask
{
    public function __construct(
        private readonly MysqlDatabase $mysqlDatabase,
        private readonly PostgresqlDatabase $postgresqlDatabase,
        private readonly ShellProcessor $shellProcessor,
        private readonly FilesystemProvider $filesystemProvider,
        private readonly GzipCompressor $compressor,
        private readonly DatabaseConnectionTester $connectionTester
    ) {}

    /**
     * Restore a snapshot to a target database server
     *
     * @throws \Exception
     */
    public function run(DatabaseServer $targetServer, Snapshot $snapshot, string $schemaName): void
    {
        // Validate compatibility
        $this->validateCompatibility($targetServer, $snapshot);

        // Test connection to target server
        $this->testConnection($targetServer);

        $workingFile = $this->getWorkingFile('local');
        $compressedFile = null;
        $filesystem = $this->filesystemProvider->get($snapshot->volume->type);

        try {
            // Download snapshot from volume
            $compressedFile = $this->download($snapshot, $filesystem);

            // Decompress the file
            $this->decompress($compressedFile, $workingFile);

            // Drop and recreate the database
            $this->prepareDatabase($targetServer, $schemaName);

            // Configure database interface with target server credentials
            $this->configureDatabaseInterface($targetServer, $schemaName);

            // Restore the database
            $this->restoreDatabase($targetServer, $workingFile);
        } finally {
            // Clean up temporary files
            if (isset($compressedFile) && file_exists($compressedFile)) {
                unlink($compressedFile);
            }
            if (file_exists($workingFile)) {
                unlink($workingFile);
            }
        }
    }

    private function validateCompatibility(DatabaseServer $targetServer, Snapshot $snapshot): void
    {
        if ($targetServer->database_type !== $snapshot->database_type) {
            throw new \Exception(
                "Cannot restore {$snapshot->database_type} snapshot to {$targetServer->database_type} server"
            );
        }
    }

    private function testConnection(DatabaseServer $targetServer): void
    {
        // For connection test, use existing database or default system database
        $testDatabase = match ($targetServer->database_type) {
            'mysql', 'mariadb' => $targetServer->database_name ?? 'mysql',
            'postgresql' => $targetServer->database_name ?? 'postgres',
            default => $targetServer->database_name,
        };

        $result = $this->connectionTester->test([
            'database_type' => $targetServer->database_type,
            'host' => $targetServer->host,
            'port' => $targetServer->port,
            'username' => $targetServer->username,
            'password' => $targetServer->password,
            'database_name' => $testDatabase,
        ]);

        if (! $result['success']) {
            throw new \Exception("Failed to connect to target server: {$result['message']}");
        }
    }

    private function download(Snapshot $snapshot, $filesystem): string
    {
        $tempFile = $this->getWorkingFile('local', 'restore-'.uniqid().'.sql.gz');

        $stream = $filesystem->readStream($snapshot->path);
        $localStream = fopen($tempFile, 'w');

        if ($stream === false || $localStream === false) {
            throw new \RuntimeException('Failed to open streams for download');
        }

        try {
            stream_copy_to_stream($stream, $localStream);

            return $tempFile;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_resource($localStream)) {
                fclose($localStream);
            }
        }
    }

    private function decompress(string $compressedFile, string $outputFile): void
    {
        // Copy the compressed file to a temporary location for decompression
        $tempCompressed = $compressedFile.'.tmp.gz';
        copy($compressedFile, $tempCompressed);

        $this->shellProcessor->process(
            Process::fromShellCommandline(
                $this->compressor->getDecompressCommandLine($tempCompressed)
            )
        );

        // After decompression, the file will be without .gz extension
        $decompressedFile = $this->compressor->getDecompressedPath($tempCompressed);

        if (! file_exists($decompressedFile)) {
            throw new \RuntimeException('Decompression failed: output file not found');
        }

        // Move to final location
        rename($decompressedFile, $outputFile);
    }

    protected function prepareDatabase(DatabaseServer $targetServer, string $schemaName): void
    {
        try {
            $pdo = $this->createConnection($targetServer);

            match ($targetServer->database_type) {
                'mysql', 'mariadb' => $this->prepareMysqlDatabase($pdo, $schemaName),
                'postgresql' => $this->preparePostgresqlDatabase($pdo, $schemaName),
                default => throw new \Exception("Database type {$targetServer->database_type} not supported"),
            };
        } catch (PDOException $e) {
            throw new \Exception("Failed to prepare database: {$e->getMessage()}", 0, $e);
        }
    }

    private function prepareMysqlDatabase(PDO $pdo, string $schemaName): void
    {
        // Drop database if exists
        $pdo->exec("DROP DATABASE IF EXISTS `{$schemaName}`");

        // Create new database
        $pdo->exec("CREATE DATABASE `{$schemaName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function preparePostgresqlDatabase(PDO $pdo, string $schemaName): void
    {
        // Check if database exists
        $stmt = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
        $stmt->execute([$schemaName]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Terminate existing connections to the database
            $pdo->exec("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$schemaName}' AND pid <> pg_backend_pid()");

            // Drop the database
            $pdo->exec("DROP DATABASE IF EXISTS \"{$schemaName}\"");
        }

        // Create new database
        $pdo->exec("CREATE DATABASE \"{$schemaName}\"");
    }

    private function restoreDatabase(DatabaseServer $targetServer, string $inputPath): void
    {
        switch ($targetServer->database_type) {
            case 'mysql':
            case 'mariadb':
                $this->shellProcessor->process(
                    Process::fromShellCommandline(
                        $this->mysqlDatabase->getRestoreCommandLine($inputPath)
                    )
                );
                break;
            case 'postgresql':
                $this->shellProcessor->process(
                    Process::fromShellCommandline(
                        $this->postgresqlDatabase->getRestoreCommandLine($inputPath)
                    )
                );
                break;
            default:
                throw new \Exception("Database type {$targetServer->database_type} not supported");
        }
    }

    private function configureDatabaseInterface(DatabaseServer $targetServer, string $schemaName): void
    {
        $config = [
            'host' => $targetServer->host,
            'port' => $targetServer->port,
            'user' => $targetServer->username,
            'pass' => $targetServer->password,
            'database' => $schemaName,
        ];

        match ($targetServer->database_type) {
            'mysql', 'mariadb' => $this->mysqlDatabase->setConfig($config),
            'postgresql' => $this->postgresqlDatabase->setConfig($config),
            default => throw new \Exception("Database type {$targetServer->database_type} not supported"),
        };
    }

    private function createConnection(DatabaseServer $targetServer): PDO
    {
        $dsn = $this->buildDsn($targetServer);

        return new PDO(
            $dsn,
            $targetServer->username,
            $targetServer->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 30,
            ]
        );
    }

    private function buildDsn(DatabaseServer $targetServer): string
    {
        return match ($targetServer->database_type) {
            'mysql', 'mariadb' => sprintf(
                'mysql:host=%s;port=%d',
                $targetServer->host,
                $targetServer->port
            ),
            'postgresql' => sprintf(
                'pgsql:host=%s;port=%d;dbname=postgres',
                $targetServer->host,
                $targetServer->port
            ),
            default => throw new \Exception("Database type {$targetServer->database_type} not supported"),
        };
    }

    private function getWorkingFile(string $name, ?string $filename = null): string
    {
        if (is_null($filename)) {
            $filename = uniqid();
        }

        return sprintf('%s/%s', $this->getRootPath($name), $filename);
    }

    private function getRootPath(string $name): string
    {
        $path = $this->filesystemProvider->getConfig($name, 'root');

        return preg_replace('/\/$/', '', $path);
    }
}

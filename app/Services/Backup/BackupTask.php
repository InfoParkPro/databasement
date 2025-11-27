<?php

namespace App\Services\Backup;

use App\Models\DatabaseServer;
use App\Models\Snapshot;
use App\Services\Backup\Databases\MysqlDatabase;
use App\Services\Backup\Databases\PostgresqlDatabase;
use App\Services\Backup\Filesystems\FilesystemProvider;
use League\Flysystem\Filesystem;
use Symfony\Component\Process\Process;

class BackupTask
{
    public function __construct(
        private readonly MysqlDatabase $mysqlDatabase,
        private readonly PostgresqlDatabase $postgresqlDatabase,
        private readonly ShellProcessor $shellProcessor,
        private readonly FilesystemProvider $filesystemProvider,
        private readonly GzipCompressor $compressor,
        private readonly DatabaseSizeCalculator $databaseSizeCalculator
    ) {}

    protected function getWorkingFile($name, $filename = null): string
    {
        if (is_null($filename)) {
            $filename = uniqid();
        }

        return sprintf('%s/%s', $this->getRootPath($name), $filename);
    }

    protected function getRootPath($name): string
    {
        $path = $this->filesystemProvider->getConfig($name, 'root');

        return preg_replace('/\/$/', '', $path);
    }

    public function run(DatabaseServer $databaseServer, string $method = 'manual', ?string $userId = null): Snapshot
    {
        // Create snapshot record
        $snapshot = $this->createSnapshot($databaseServer, $method, $userId);

        $workingFile = $this->getWorkingFile('local');
        $filesystem = $this->filesystemProvider->get($databaseServer->backup->volume->type);

        // Configure database interface with server credentials
        $this->configureDatabaseInterface($databaseServer);

        try {
            // Mark as running
            $snapshot->update(['status' => 'running']);

            // Execute backup
            $this->dumpDatabase($databaseServer, $workingFile);
            $archive = $this->compress($workingFile);
            $destinationPath = $this->transfer($databaseServer, $archive, $filesystem);

            // Calculate file size and checksum
            $fileSize = filesize($archive);
            $checksum = hash_file('sha256', $archive);

            // Update snapshot with success
            $snapshot->update([
                'path' => $destinationPath,
                'file_size' => $fileSize,
                'checksum' => $checksum,
            ]);

            $snapshot->markCompleted();

            return $snapshot;
        } catch (\Throwable $e) {
            $snapshot->markFailed($e);
            throw $e;
        } finally {
            // Clean up temporary files
            if (file_exists($workingFile)) {
                unlink($workingFile);
            }
            if (isset($archive) && file_exists($archive)) {
                unlink($archive);
            }
        }
    }

    private function dumpDatabase(DatabaseServer $databaseServer, string $outputPath): void
    {
        switch ($databaseServer->database_type) {
            case 'mysql':
            case 'mariadb':
                $this->shellProcessor->process(
                    Process::fromShellCommandline(
                        $this->mysqlDatabase->getDumpCommandLine($outputPath)
                    )
                );
                break;
            case 'postgresql':
                $this->shellProcessor->process(
                    Process::fromShellCommandline(
                        $this->postgresqlDatabase->getDumpCommandLine($outputPath)
                    )
                );
                break;
            default:
                throw new \Exception("Database type {$databaseServer->database_type} not supported");
        }
    }

    private function compress(string $path): string
    {
        $this->shellProcessor->process(
            Process::fromShellCommandline(
                $this->compressor->getCompressCommandLine($path)
            )
        );

        return $this->compressor->getCompressedPath($path);
    }

    private function transfer(DatabaseServer $databaseServer, string $path, Filesystem $filesystem): string
    {
        $stream = fopen($path, 'r');
        if ($stream === false) {
            throw new \RuntimeException("Failed to open file: {$path}");
        }

        try {
            $destinationPath = $this->generateBackupFilename($databaseServer);
            $filesystem->writeStream($destinationPath, $stream);

            return $destinationPath;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function generateBackupFilename(DatabaseServer $databaseServer): string
    {
        $timestamp = now()->format('Y-m-d-His');
        $serverName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $databaseServer->name);
        $databaseName = preg_replace('/[^a-zA-Z0-9-_]/', '-', $databaseServer->database_name ?? 'db');

        return sprintf('%s-%s-%s.sql.gz', $serverName, $databaseName, $timestamp);
    }

    private function configureDatabaseInterface(DatabaseServer $databaseServer): void
    {
        $config = [
            'host' => $databaseServer->host,
            'port' => $databaseServer->port,
            'user' => $databaseServer->username,
            'pass' => $databaseServer->password,
            'database' => $databaseServer->database_name,
        ];

        match ($databaseServer->database_type) {
            'mysql', 'mariadb' => $this->mysqlDatabase->setConfig($config),
            'postgresql' => $this->postgresqlDatabase->setConfig($config),
            default => throw new \Exception("Database type {$databaseServer->database_type} not supported"),
        };
    }

    private function createSnapshot(DatabaseServer $databaseServer, string $method, ?string $userId): Snapshot
    {
        // Calculate database size
        $databaseSize = $this->databaseSizeCalculator->calculate($databaseServer);

        return Snapshot::create([
            'database_server_id' => $databaseServer->id,
            'backup_id' => $databaseServer->backup->id,
            'volume_id' => $databaseServer->backup->volume_id,
            'path' => '', // Will be updated after transfer
            'file_size' => 0, // Will be updated after transfer
            'checksum' => null, // Will be updated after transfer
            'started_at' => now(),
            'completed_at' => null,
            'status' => 'pending',
            'error_message' => null,
            'error_trace' => null,
            'database_name' => $databaseServer->database_name ?? '',
            'database_type' => $databaseServer->database_type,
            'database_host' => $databaseServer->host,
            'database_port' => $databaseServer->port,
            'database_size_bytes' => $databaseSize,
            'compression_type' => 'gzip',
            'method' => $method,
            'triggered_by_user_id' => $userId,
        ]);
    }
}

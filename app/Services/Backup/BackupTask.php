<?php

namespace App\Services\Backup;

use App\Models\DatabaseServer;
use App\Services\Backup\Databases\MysqlDatabaseInterface;
use App\Services\Backup\Databases\PostgresqlDatabaseInterface;
use App\Services\Backup\Filesystems\FilesystemProvider;
use League\Flysystem\Filesystem;
use Symfony\Component\Process\Process;

class BackupTask
{
    public function __construct(
        private readonly MysqlDatabaseInterface $mysqlDatabase,
        private readonly PostgresqlDatabaseInterface $postgresqlDatabase,
        private readonly ShellProcessor $shellProcessor,
        private readonly FilesystemProvider $filesystemProvider,
        private readonly GzipCompressor $compressor
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

    public function run(DatabaseServer $databaseServer)
    {
        $workingFile = $this->getWorkingFile('local');
        $filesystem = $this->filesystemProvider->get($databaseServer->backup->volume->type);

        $this->dumpDatabase($databaseServer, $workingFile);
        $archive = $this->compress($workingFile);
        $this->transfer($archive, $filesystem);
    }

    private function dumpDatabase(DatabaseServer $databaseServer, string $outputPath): void
    {
        switch ($databaseServer->type) {
            case 'mysql':
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
                throw new \Exception('Database type not supported');
        }
    }

    private function compress(string $path): string
    {
        $this->shellProcessor->process(
            Process::fromShellCommandline(
                $this->compressor->getCompressCommandLine($path)
            )
        );

        return $this->compressor->getDecompressedPath($path);
    }

    private function transfer(string $path, Filesystem $filesystem): void
    {
        $filesystem->writeStream($path, fopen($path, 'r'));
    }
}

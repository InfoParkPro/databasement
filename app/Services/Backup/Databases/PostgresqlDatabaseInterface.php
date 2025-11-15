<?php

namespace App\Services\Backup\Databases;

class PostgresqlDatabaseInterface implements DatabaseInterface
{
    private array $config;

    public function handles($type): bool
    {
        return in_array(strtolower($type ?? ''), ['postgresql', 'postgres', 'pgsql']);
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getDumpCommandLine($outputPath): string
    {
        return sprintf(
            'PGPASSWORD=%s pg_dump --clean --host=%s --port=%s --username=%s %s -f %s',
            escapeshellarg($this->config['pass']),
            escapeshellarg($this->config['host']),
            escapeshellarg($this->config['port']),
            escapeshellarg($this->config['user']),
            escapeshellarg($this->config['database']),
            escapeshellarg($outputPath)
        );
    }

    public function getRestoreCommandLine($inputPath): string
    {
        return sprintf(
            'PGPASSWORD=%s psql --host=%s --port=%s --user=%s %s -f %s',
            escapeshellarg($this->config['pass']),
            escapeshellarg($this->config['host']),
            escapeshellarg($this->config['port']),
            escapeshellarg($this->config['user']),
            escapeshellarg($this->config['database']),
            escapeshellarg($inputPath)
        );
    }
}

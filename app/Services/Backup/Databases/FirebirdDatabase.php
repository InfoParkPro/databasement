<?php

namespace App\Services\Backup\Databases;

use App\Contracts\BackupLogger;
use App\Exceptions\Backup\UnsupportedDatabaseTypeException;
use App\Services\Backup\DTO\DatabaseOperationResult;

class FirebirdDatabase implements DatabaseInterface
{
    /** @var array<string, mixed> */
    private array $config = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function dump(string $outputPath): DatabaseOperationResult
    {
        return new DatabaseOperationResult(command: 'gbak');
    }

    public function restore(string $inputPath): DatabaseOperationResult
    {
        throw new UnsupportedDatabaseTypeException('firebird');
    }

    public function prepareForRestore(string $schemaName, BackupLogger $logger): void
    {
        throw new UnsupportedDatabaseTypeException('firebird');
    }

    public function listDatabases(): array
    {
        return isset($this->config['database']) ? [(string) $this->config['database']] : [];
    }

    public function testConnection(): array
    {
        return [
            'success' => false,
            'message' => 'Firebird connection test is not implemented yet',
            'details' => [],
        ];
    }
}

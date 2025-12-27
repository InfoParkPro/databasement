<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\DatabaseServer;
use App\Models\Volume;
use RuntimeException;

class DemoBackupService
{
    /**
     * Create a demo backup configuration for the application's own database.
     *
     * @throws RuntimeException If database connection is SQLite (not supported)
     */
    public function createDemoBackup(): DatabaseServer
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            throw new RuntimeException('SQLite database backups are not yet supported.');
        }

        $dbConfig = config("database.connections.{$connection}");

        $databaseType = match ($connection) {
            'mysql' => 'mysql',
            'mariadb' => 'mariadb',
            'pgsql' => 'postgresql',
            default => throw new RuntimeException("Unsupported database connection: {$connection}"),
        };

        // Create local volume for backups
        $volume = Volume::create([
            'name' => 'Local Backups',
            'type' => 'local',
            'config' => [
                'path' => '/data/backups',
            ],
        ]);

        // Create database server entry
        $databaseServer = DatabaseServer::create([
            'name' => 'Databasement Database',
            'host' => $dbConfig['host'] ?? '127.0.0.1',
            'port' => (int) ($dbConfig['port'] ?? ($databaseType === 'postgresql' ? 5432 : 3306)),
            'database_type' => $databaseType,
            'username' => $dbConfig['username'] ?? '',
            'password' => $dbConfig['password'] ?? '',
            'database_names' => [$dbConfig['database'] ?? 'databasement'],
            'description' => 'Demo backup of the Databasement application database',
        ]);

        // Create backup configuration
        Backup::create([
            'database_server_id' => $databaseServer->id,
            'volume_id' => $volume->id,
            'recurrence' => 'daily',
            'retention_days' => 14,
        ]);

        return $databaseServer->load('backup.volume');
    }
}

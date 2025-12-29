<?php

/**
 * Integration tests for backup and restore with real databases.
 *
 * These tests require MySQL and PostgreSQL containers to be running.
 * Run with: php artisan test --group=integration
 */

use App\Models\Backup;
use App\Models\DatabaseServer;
use App\Models\Volume;
use App\Services\Backup\BackupJobFactory;
use App\Services\Backup\BackupTask;
use App\Services\Backup\Filesystems\FilesystemProvider;
use App\Services\Backup\RestoreTask;

uses()->group('integration');

beforeEach(function () {
    $this->backupTask = app(BackupTask::class);
    $this->restoreTask = app(RestoreTask::class);
    $this->backupJobFactory = app(BackupJobFactory::class);
    $this->filesystemProvider = app(FilesystemProvider::class);

    $this->volume = null;
    $this->databaseServer = null;
    $this->backup = null;
    $this->snapshot = null;
    $this->restoredDatabaseName = null;
});

afterEach(function () {
    // Cleanup restored database
    if ($this->restoredDatabaseName && $this->databaseServer) {
        try {
            integrationDropRestoredDatabase(
                $this->databaseServer->database_type === 'postgresql' ? 'postgres' : 'mysql',
                $this->databaseServer,
                $this->restoredDatabaseName
            );
        } catch (Exception) {
            // Ignore cleanup errors
        }
    }

    // Delete models (cascade handles backup and snapshots)
    $this->databaseServer?->delete();
    $this->volume?->delete();
});

test('client-server database backup and restore workflow', function (string $type) {
    // Create models
    $this->volume = integrationCreateVolume($type);
    $this->databaseServer = integrationCreateDatabaseServer($type);
    $this->backup = integrationCreateBackup($this->databaseServer, $this->volume);
    $this->databaseServer->load('backup.volume');

    // Load test data
    integrationLoadTestData($type, $this->databaseServer);

    // Run backup
    $snapshots = $this->backupJobFactory->createSnapshots(
        server: $this->databaseServer,
        method: 'manual',
    );
    $this->snapshot = $snapshots[0];
    $this->backupTask->run($this->snapshot);
    $this->snapshot->refresh();
    $this->snapshot->load('job');

    $filesystem = $this->filesystemProvider->getForVolume($this->snapshot->volume);

    expect($this->snapshot->job->status)->toBe('completed')
        ->and($this->snapshot->file_size)->toBeGreaterThan(0)
        ->and($filesystem->fileExists($this->snapshot->getStoragePath()))->toBeTrue();

    // Run restore
    $this->restoredDatabaseName = 'testdb_restored_'.time();
    $restore = $this->backupJobFactory->createRestore(
        snapshot: $this->snapshot,
        targetServer: $this->databaseServer,
        schemaName: $this->restoredDatabaseName,
    );
    $this->restoreTask->run($restore);

    // Verify restore
    $pdo = integrationConnectToDatabase($type, $this->databaseServer, $this->restoredDatabaseName);
    expect($pdo)->toBeInstanceOf(PDO::class);

    $verifyQuery = match ($type) {
        'mysql' => 'SHOW TABLES',
        'postgres' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'",
    };
    $stmt = $pdo->query($verifyQuery);
    expect($stmt)->not->toBeFalse();
})->with(['mysql', 'postgres']);

test('sqlite backup and restore workflow', function () {
    // Create a test SQLite database with some data
    $backupDir = config('backup.working_directory');
    $sourceSqlitePath = "{$backupDir}/test_source.sqlite";
    $restoredSqlitePath = "{$backupDir}/test_restored_".time().'.sqlite';
    integrationCreateTestSqliteDatabase($sourceSqlitePath);

    // Create models
    $this->volume = integrationCreateVolume('sqlite');
    $this->databaseServer = integrationCreateSqliteDatabaseServer($sourceSqlitePath);
    $this->backup = integrationCreateBackup($this->databaseServer, $this->volume);
    $this->databaseServer->load('backup.volume');

    // Run backup
    $snapshots = $this->backupJobFactory->createSnapshots(
        server: $this->databaseServer,
        method: 'manual',
        triggeredByUserId: null
    );
    $this->snapshot = $snapshots[0];
    $this->backupTask->run($this->snapshot);
    $this->snapshot->refresh();
    $this->snapshot->load('job');

    $filesystem = $this->filesystemProvider->getForVolume($this->snapshot->volume);

    expect($this->snapshot->job->status)->toBe('completed')
        ->and($this->snapshot->file_size)->toBeGreaterThan(0)
        ->and($this->snapshot->getDatabaseServerMetadata()['host'])->toBeNull()
        ->and($filesystem->fileExists($this->snapshot->getStoragePath()))->toBeTrue();

    // Create a target server for restore (different sqlite file)
    $targetServer = integrationCreateSqliteDatabaseServer($restoredSqlitePath);
    Backup::create([
        'database_server_id' => $targetServer->id,
        'volume_id' => $this->volume->id,
        'recurrence' => 'manual',
    ]);

    // Run restore
    $restore = $this->backupJobFactory->createRestore(
        snapshot: $this->snapshot,
        targetServer: $targetServer,
        schemaName: $restoredSqlitePath,
    );
    $this->restoreTask->run($restore);

    // Verify restore - check that the restored database has the test data
    $pdo = new PDO("sqlite:{$restoredSqlitePath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM test_table');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    expect((int) $result['count'])->toBe(3);

    // Cleanup
    @unlink($sourceSqlitePath);
    @unlink($restoredSqlitePath);
    $targetServer->delete();
});

function integrationCreateVolume(string $type): Volume
{
    $storageDir = config('backup.working_directory').'/storage';
    if (! is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    return Volume::create([
        'name' => "Integration Test Volume ({$type})",
        'type' => 'local',
        'config' => ['root' => $storageDir],
    ]);
}

function integrationCreateDatabaseServer(string $type): DatabaseServer
{
    $config = match ($type) {
        'mysql' => [
            'name' => 'Integration Test MySQL Server',
            'host' => config('testing.databases.mysql.host'),
            'port' => config('testing.databases.mysql.port'),
            'database_type' => 'mysql',
            'username' => config('testing.databases.mysql.username'),
            'password' => config('testing.databases.mysql.password'),
            'database_names' => [config('testing.databases.mysql.database')],
            'description' => 'Integration test MySQL database server',
        ],
        'postgres' => [
            'name' => 'Integration Test PostgreSQL Server',
            'host' => config('testing.databases.postgres.host'),
            'port' => config('testing.databases.postgres.port'),
            'database_type' => 'postgresql',
            'username' => config('testing.databases.postgres.username'),
            'password' => config('testing.databases.postgres.password'),
            'database_names' => [config('testing.databases.postgres.database')],
            'description' => 'Integration test PostgreSQL database server',
        ],
        default => throw new InvalidArgumentException("Unsupported database type: {$type}"),
    };

    return DatabaseServer::create($config);
}

function integrationCreateBackup(DatabaseServer $server, Volume $volume): Backup
{
    return Backup::create([
        'database_server_id' => $server->id,
        'volume_id' => $volume->id,
        'recurrence' => 'manual',
    ]);
}

function integrationCreateSqliteDatabaseServer(string $sqlitePath): DatabaseServer
{
    return DatabaseServer::create([
        'name' => 'Integration Test SQLite Server',
        'database_type' => 'sqlite',
        'sqlite_path' => $sqlitePath,
        'description' => 'Integration test SQLite database',
    ]);
}

function integrationCreateTestSqliteDatabase(string $path): void
{
    // Remove if exists
    if (file_exists($path)) {
        unlink($path);
    }

    // Create a new SQLite database with test data
    $pdo = new PDO("sqlite:{$path}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT, value INTEGER)');
    $pdo->exec("INSERT INTO test_table (name, value) VALUES ('item1', 100)");
    $pdo->exec("INSERT INTO test_table (name, value) VALUES ('item2', 200)");
    $pdo->exec("INSERT INTO test_table (name, value) VALUES ('item3', 300)");
}

function integrationConnectToDatabase(string $type, DatabaseServer $server, string $databaseName): PDO
{
    $dsn = match ($type) {
        'mysql' => sprintf('mysql:host=%s;port=%d;dbname=%s', $server->host, $server->port, $databaseName),
        'postgres' => sprintf('pgsql:host=%s;port=%d;dbname=%s', $server->host, $server->port, $databaseName),
        default => throw new InvalidArgumentException("Unsupported database type: {$type}"),
    };

    return new PDO($dsn, $server->username, $server->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function integrationDropRestoredDatabase(string $type, DatabaseServer $server, string $databaseName): void
{
    $dsn = match ($type) {
        'mysql' => sprintf('mysql:host=%s;port=%d', $server->host, $server->port),
        'postgres' => sprintf('pgsql:host=%s;port=%d;dbname=postgres', $server->host, $server->port),
        default => throw new InvalidArgumentException("Unsupported database type: {$type}"),
    };

    $pdo = new PDO($dsn, $server->username, $server->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    if ($type === 'mysql') {
        $pdo->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    } elseif ($type === 'postgres') {
        $pdo->exec("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$databaseName}' AND pid <> pg_backend_pid()");
        $pdo->exec("DROP DATABASE IF EXISTS \"{$databaseName}\"");
    }
}

function integrationLoadTestData(string $type, DatabaseServer $server): void
{
    $databaseName = $server->database_names[0];

    // Connect to server (not specific database)
    $dsn = match ($type) {
        'mysql' => sprintf('mysql:host=%s;port=%d', $server->host, $server->port),
        'postgres' => sprintf('pgsql:host=%s;port=%d;dbname=postgres', $server->host, $server->port),
        default => throw new InvalidArgumentException("Unsupported database type: {$type}"),
    };

    $pdo = new PDO($dsn, $server->username, $server->password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Drop database if exists
    if ($type === 'mysql') {
        $pdo->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
        $pdo->exec("CREATE DATABASE `{$databaseName}`");
    } else {
        $pdo->exec("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$databaseName}' AND pid <> pg_backend_pid()");
        $pdo->exec("DROP DATABASE IF EXISTS \"{$databaseName}\"");
        $pdo->exec("CREATE DATABASE \"{$databaseName}\"");
    }

    // Connect to new database and load fixture
    $fixtureFile = match ($type) {
        'mysql' => __DIR__.'/fixtures/mysql-init.sql',
        'postgres' => __DIR__.'/fixtures/postgres-init.sql',
    };

    $pdo = integrationConnectToDatabase($type, $server, $databaseName);
    $pdo->exec(file_get_contents($fixtureFile));
}

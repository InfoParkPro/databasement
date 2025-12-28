<?php

use App\Models\Backup;
use App\Models\DatabaseServer;
use App\Models\Volume;
use App\Services\DemoBackupService;

test('creates demo backup for sqlite database', function () {
    // Test is run with SQLite by default (phpunit.xml config)
    // This mirrors the production scenario where the app uses SQLite
    config(['database.connections.sqlite.database' => '/data/database.sqlite']);

    $service = new DemoBackupService;
    $databaseServer = $service->createDemoBackup();

    expect($databaseServer)->toBeInstanceOf(DatabaseServer::class)
        ->and($databaseServer->database_type)->toBe('sqlite')
        ->and($databaseServer->sqlite_path)->toBe('/data/database.sqlite')
        ->and($databaseServer->host)->toBeNull()
        ->and($databaseServer->username)->toBeNull()
        ->and(Volume::count())->toBe(1)
        ->and(Backup::count())->toBe(1)
        ->and($databaseServer->backup)->not->toBeNull();

});

test('creates demo backup for mysql database', function () {
    // Set up a mysql connection config that the service will read
    config(['database.connections.mysql' => [
        'driver' => 'mysql',
        'host' => config('backup.backup_test.mysql.host'),
        'port' => config('backup.backup_test.mysql.port'),
        'database' => config('backup.backup_test.mysql.database'),
        'username' => config('backup.backup_test.mysql.username'),
        'password' => config('backup.backup_test.mysql.password'),
    ]]);

    $service = new DemoBackupService;
    // Use the connectionName parameter to specify mysql without changing the default connection
    $databaseServer = $service->createDemoBackup('mysql');

    expect($databaseServer)->toBeInstanceOf(DatabaseServer::class)
        ->and($databaseServer->database_type)->toBe('mysql')
        ->and($databaseServer->host)->toBe(config('backup.backup_test.mysql.host'))
        ->and($databaseServer->port)->toBe((int)config('backup.backup_test.mysql.port'))
        ->and($databaseServer->username)->toBe(config('backup.backup_test.mysql.username'))
        ->and($databaseServer->sqlite_path)->toBeNull()
        ->and(Volume::count())->toBe(1)
        ->and(Backup::count())->toBe(1)
        ->and($databaseServer->backup)->not->toBeNull();

});

test('creates demo backup for postgresql database', function () {
    // Set up a pgsql connection config that the service will read
    config(['database.connections.pgsql' => [
        'driver' => 'pgsql',
        'host' => config('backup.backup_test.postgres.host'),
        'port' => config('backup.backup_test.postgres.port'),
        'database' => config('backup.backup_test.postgres.database'),
        'username' => config('backup.backup_test.postgres.username'),
        'password' => config('backup.backup_test.postgres.password'),
    ]]);

    $service = new DemoBackupService;
    // Use the connectionName parameter to specify pgsql without changing the default connection
    $databaseServer = $service->createDemoBackup('pgsql');

    expect($databaseServer)->toBeInstanceOf(DatabaseServer::class)
        ->and($databaseServer->database_type)->toBe('postgresql')
        ->and($databaseServer->host)->toBe(config('backup.backup_test.postgres.host'))
        ->and($databaseServer->port)->toBe((int)config('backup.backup_test.postgres.port'))
        ->and($databaseServer->username)->toBe(config('backup.backup_test.postgres.username'))
        ->and($databaseServer->sqlite_path)->toBeNull()
        ->and(Volume::count())->toBe(1)
        ->and(Backup::count())->toBe(1)
        ->and($databaseServer->backup)->not->toBeNull();

});

test('throws exception for unsupported database type', function () {
    $service = new DemoBackupService;
    $service->createDemoBackup('mongodb');
})->throws(RuntimeException::class, 'Unsupported database connection: mongodb');

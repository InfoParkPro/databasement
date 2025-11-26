<?php

use App\Models\DatabaseServer;
use App\Services\Backup\DatabaseListService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('listDatabases returns mysql databases excluding system databases', function () {
    // This test requires a real MySQL connection, so we'll skip it in CI
    // You can enable this locally if you have MySQL running
    if (! env('TEST_MYSQL_ENABLED', false)) {
        $this->markTestSkipped('MySQL testing not enabled. Set TEST_MYSQL_ENABLED=true in .env.testing');
    }

    // Arrange
    $server = DatabaseServer::create([
        'name' => 'Test MySQL',
        'host' => env('TEST_MYSQL_HOST', '127.0.0.1'),
        'port' => env('TEST_MYSQL_PORT', 3306),
        'database_type' => 'mysql',
        'username' => env('TEST_MYSQL_USERNAME', 'admin'),
        'password' => env('TEST_MYSQL_PASSWORD', 'admin'),
        'database_name' => 'testdb',
    ]);

    $service = new DatabaseListService;

    // Act
    $databases = $service->listDatabases($server);

    // Assert
    expect($databases)->toBeArray();
    expect($databases)->not->toContain('information_schema');
    expect($databases)->not->toContain('performance_schema');
    expect($databases)->not->toContain('mysql');
    expect($databases)->not->toContain('sys');
})->skip();

test('listDatabases returns postgresql databases excluding system databases', function () {
    // This test requires a real PostgreSQL connection, so we'll skip it in CI
    if (! env('TEST_POSTGRES_ENABLED', false)) {
        $this->markTestSkipped('PostgreSQL testing not enabled. Set TEST_POSTGRES_ENABLED=true in .env.testing');
    }

    // Arrange
    $server = DatabaseServer::create([
        'name' => 'Test PostgreSQL',
        'host' => env('TEST_POSTGRES_HOST', '127.0.0.1'),
        'port' => env('TEST_POSTGRES_PORT', 5432),
        'database_type' => 'postgresql',
        'username' => env('TEST_POSTGRES_USERNAME', 'admin'),
        'password' => env('TEST_POSTGRES_PASSWORD', 'admin'),
        'database_name' => 'testdb',
    ]);

    $service = new DatabaseListService;

    // Act
    $databases = $service->listDatabases($server);

    // Assert
    expect($databases)->toBeArray();
    expect($databases)->not->toContain('postgres');
    expect($databases)->not->toContain('template0');
    expect($databases)->not->toContain('template1');
})->skip();

test('listDatabases throws exception on connection failure', function () {
    // Arrange
    $server = DatabaseServer::create([
        'name' => 'Invalid MySQL',
        'host' => 'invalid-host-that-does-not-exist',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => 'testdb',
    ]);

    $service = new DatabaseListService;

    // Act & Assert
    expect(fn () => $service->listDatabases($server))
        ->toThrow(\Exception::class, 'Failed to list databases');
});

test('listDatabases throws exception for unsupported database type', function () {
    // Arrange
    $server = DatabaseServer::create([
        'name' => 'Oracle DB',
        'host' => 'localhost',
        'port' => 1521,
        'database_type' => 'oracle',
        'username' => 'system',
        'password' => 'oracle',
        'database_name' => 'orcl',
    ]);

    $service = new DatabaseListService;

    // Act & Assert
    expect(fn () => $service->listDatabases($server))
        ->toThrow(\Exception::class, 'Database type oracle not supported');
});

test('listDatabases handles mariadb database type', function () {
    // MariaDB uses the same connection as MySQL but with different type
    if (! env('TEST_MYSQL_ENABLED', false)) {
        $this->markTestSkipped('MySQL/MariaDB testing not enabled');
    }

    // Arrange
    $server = DatabaseServer::create([
        'name' => 'Test MariaDB',
        'host' => env('TEST_MYSQL_HOST', '127.0.0.1'),
        'port' => env('TEST_MYSQL_PORT', 3306),
        'database_type' => 'mariadb',
        'username' => env('TEST_MYSQL_USERNAME', 'admin'),
        'password' => env('TEST_MYSQL_PASSWORD', 'admin'),
        'database_name' => 'testdb',
    ]);

    $service = new DatabaseListService;

    // Act
    $databases = $service->listDatabases($server);

    // Assert
    expect($databases)->toBeArray();
    expect($databases)->not->toContain('information_schema');
})->skip();

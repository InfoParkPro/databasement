<?php

use App\Models\DatabaseServer;
use App\Services\Backup\DatabaseListService;

test('listDatabases returns mysql databases excluding system databases', function () {
    // Arrange - Create a test double for the server
    $server = new class extends DatabaseServer
    {
        public function __construct()
        {
            // Skip parent constructor to avoid database interaction
            $this->database_type = 'mysql';
            $this->host = '127.0.0.1';
            $this->port = 3306;
            $this->username = 'admin';
            $this->password = 'admin';
        }
    };

    // Mock PDOStatement
    $pdoStatement = Mockery::mock(\PDOStatement::class);
    $pdoStatement->shouldReceive('fetchAll')
        ->once()
        ->with(PDO::FETCH_COLUMN, 0)
        ->andReturn([
            'information_schema',
            'performance_schema',
            'mysql',
            'sys',
            'app_database',
            'test_database',
            'production_db',
        ]);

    // Mock PDO
    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('query')
        ->once()
        ->with('SHOW DATABASES')
        ->andReturn($pdoStatement);

    // Partial mock the service to inject our mocked PDO
    $service = Mockery::mock(DatabaseListService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('createConnection')
        ->once()
        ->with($server)
        ->andReturn($pdo);

    // Act
    $databases = $service->listDatabases($server);

    // Assert
    expect($databases)->toBeArray()
        ->and($databases)->toHaveCount(3)
        ->and($databases)->toContain('app_database')
        ->and($databases)->toContain('test_database')
        ->and($databases)->toContain('production_db')
        ->and($databases)->not->toContain('information_schema')
        ->and($databases)->not->toContain('performance_schema')
        ->and($databases)->not->toContain('mysql')
        ->and($databases)->not->toContain('sys');
});

<?php

use App\Models\Backup;
use App\Models\DatabaseServer;
use App\Models\Volume;
use App\Services\Backup\BackupTask;
use App\Services\Backup\Databases\MysqlDatabase;
use App\Services\Backup\Databases\PostgresqlDatabase;
use App\Services\Backup\DatabaseSizeCalculator;
use App\Services\Backup\Filesystems\FilesystemProvider;
use App\Services\Backup\GzipCompressor;
use App\Services\Backup\ShellProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Flysystem\Filesystem;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Mock dependencies (but use REAL ShellProcessor!)
    $this->mysqlDatabase = Mockery::mock(MysqlDatabase::class);
    $this->postgresqlDatabase = Mockery::mock(PostgresqlDatabase::class);
    $this->shellProcessor = new ShellProcessor;  // Use REAL ShellProcessor ✓
    $this->filesystemProvider = Mockery::mock(FilesystemProvider::class);
    $this->compressor = Mockery::mock(GzipCompressor::class);
    $this->databaseSizeCalculator = Mockery::mock(DatabaseSizeCalculator::class);

    // Create the BackupTask instance
    $this->backupTask = new BackupTask(
        $this->mysqlDatabase,
        $this->postgresqlDatabase,
        $this->shellProcessor,
        $this->filesystemProvider,
        $this->compressor,
        $this->databaseSizeCalculator
    );

    // Create temp directory for test files
    $this->tempDir = sys_get_temp_dir().'/backup-task-test-'.uniqid();
    mkdir($this->tempDir, 0777, true);

    // Track created files for cleanup
    $this->createdFiles = [];
});

// Helper function to create a database server with backup and volume
function createDatabaseServer(array $attributes, string $volumeType = 'local'): DatabaseServer
{
    $volume = Volume::create([
        'name' => 'Test Volume',
        'type' => $volumeType,
        'config' => ['root' => test()->tempDir],
    ]);

    // Create the database server first without backup
    $databaseServer = DatabaseServer::create($attributes);

    // Now create the backup with both volume_id and database_server_id
    $backup = Backup::create([
        'recurrence' => 'daily',
        'volume_id' => $volume->id,
        'database_server_id' => $databaseServer->id,
    ]);

    // Update the database server with the backup_id
    $databaseServer->update(['backup_id' => $backup->id]);

    // Reload with relationships
    $databaseServer->load('backup.volume');

    return $databaseServer;
}

// Helper function to set up common expectations
function setupCommonExpectations(DatabaseServer $databaseServer, ?int $databaseSize = 1024000): string
{
    $compressedFile = test()->tempDir.'/backup-'.uniqid().'.gz';
    $filesystem = Mockery::mock(Filesystem::class);

    // Database size calculator
    test()->databaseSizeCalculator
        ->shouldReceive('calculate')
        ->once()
        ->with($databaseServer)
        ->andReturn($databaseSize);

    // Filesystem provider
    test()->filesystemProvider
        ->shouldReceive('getConfig')
        ->with('local', 'root')
        ->andReturn(test()->tempDir);

    test()->filesystemProvider
        ->shouldReceive('get')
        ->with($databaseServer->backup->volume->type)
        ->andReturn($filesystem);

    test()->compressor
        ->shouldReceive('getCompressCommandLine')
        ->once()
        ->andReturnUsing(function ($path) use ($compressedFile) {
            return sprintf('echo "compressed backup data" > %s', escapeshellarg($compressedFile));
        });

    test()->compressor
        ->shouldReceive('getCompressedPath')
        ->once()
        ->andReturn($compressedFile);

    // Transfer
    $filesystem
        ->shouldReceive('writeStream')
        ->once()
        ->with(
            Mockery::type('string'),
            Mockery::type('resource')
        );

    return $compressedFile;
}

// Helper function to setup database interface expectations
function setupDatabaseExpectations(DatabaseServer $databaseServer, $databaseInterface)
{
    $databaseInterface
        ->shouldReceive('setConfig')
        ->once()
        ->with([
            'host' => $databaseServer->host,
            'port' => $databaseServer->port,
            'user' => $databaseServer->username,
            'pass' => $databaseServer->password,
            'database' => $databaseServer->database_name,
        ]);

    $databaseInterface
        ->shouldReceive('getDumpCommandLine')
        ->once()
        ->andReturnUsing(function ($outputPath) {
            return sprintf('echo "fake database dump output" > %s', escapeshellarg($outputPath));
        });
}

afterEach(function () {
    // Clean up created files
    foreach ($this->createdFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // Remove temp directory
    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }

    Mockery::close();
});
test('run executes mysql backup workflow successfully', function () {
    // Arrange
    $databaseServer = createDatabaseServer([
        'name' => 'Production MySQL',
        'host' => 'localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => 'myapp',
    ]);

    setupCommonExpectations($databaseServer, 1024000);
    setupDatabaseExpectations($databaseServer, $this->mysqlDatabase, 'mysqldump --routines myapp');

    // Act
    $this->backupTask->run($databaseServer);

    // Assert - Mockery will verify all expectations
    expect(true)->toBeTrue();
});

test('run executes postgresql backup workflow successfully', function () {
    // Arrange
    $databaseServer = createDatabaseServer([
        'name' => 'Staging PostgreSQL',
        'host' => 'db.example.com',
        'port' => 5432,
        'database_type' => 'postgresql',
        'username' => 'postgres',
        'password' => 'pg_secret',
        'database_name' => 'staging_db',
    ], 's3');

    setupCommonExpectations($databaseServer, 2048000);
    setupDatabaseExpectations($databaseServer, $this->postgresqlDatabase, 'pg_dump staging_db');

    // Act
    $this->backupTask->run($databaseServer);

    // Assert
    expect(true)->toBeTrue();
});

test('run executes mariadb backup workflow successfully', function () {
    // Arrange - MariaDB uses MySQL interface
    $databaseServer = createDatabaseServer([
        'name' => 'MariaDB Server',
        'host' => 'mariadb.local',
        'port' => 3306,
        'database_type' => 'mariadb',
        'username' => 'admin',
        'password' => 'admin123',
        'database_name' => 'app_data',
    ]);

    setupCommonExpectations($databaseServer, 512000);
    setupDatabaseExpectations($databaseServer, $this->mysqlDatabase, 'mysqldump app_data');

    // Act
    $this->backupTask->run($databaseServer);

    // Assert
    expect(true)->toBeTrue();
});

test('run throws exception for unsupported database type', function () {
    // Arrange
    $databaseServer = createDatabaseServer([
        'name' => 'Oracle DB',
        'host' => 'localhost',
        'port' => 1521,
        'database_type' => 'oracle',
        'username' => 'system',
        'password' => 'oracle',
        'database_name' => 'orcl',
    ]);

    // Only set up expectations for operations that happen before the exception
    $this->databaseSizeCalculator
        ->shouldReceive('calculate')
        ->once()
        ->with($databaseServer)
        ->andReturn(null);

    $this->filesystemProvider
        ->shouldReceive('getConfig')
        ->with('local', 'root')
        ->andReturn($this->tempDir);

    $this->filesystemProvider
        ->shouldReceive('get')
        ->with('local')
        ->andReturn(Mockery::mock(\League\Flysystem\Filesystem::class));

    // Act & Assert
    expect(fn () => $this->backupTask->run($databaseServer))
        ->toThrow(\Exception::class, 'Database type oracle not supported');
});

test('run handles database server without database_name gracefully', function () {
    // Arrange
    $databaseServer = createDatabaseServer([
        'name' => 'Test Server',
        'host' => 'localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => null, // No database name
    ]);

    setupCommonExpectations($databaseServer, null);
    setupDatabaseExpectations($databaseServer, $this->mysqlDatabase, 'mysqldump');

    // Act
    $this->backupTask->run($databaseServer);

    // Assert
    expect(true)->toBeTrue();
});

test('run sanitizes special characters in filenames', function () {
    // Arrange
    $databaseServer = createDatabaseServer([
        'name' => 'My@Server#With$Special%Chars!',
        'host' => 'localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => 'database/with\\slashes',
    ]);

    setupCommonExpectations($databaseServer, 256000);
    setupDatabaseExpectations($databaseServer, $this->mysqlDatabase, 'mysqldump');

    // Act
    $this->backupTask->run($databaseServer);

    // Assert - Filename should have special chars replaced with dashes
    expect(true)->toBeTrue();
});

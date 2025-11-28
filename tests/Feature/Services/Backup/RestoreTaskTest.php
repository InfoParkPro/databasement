<?php

use App\Models\Backup;
use App\Models\DatabaseServer;
use App\Models\Snapshot;
use App\Models\Volume;
use App\Services\Backup\Databases\MysqlDatabase;
use App\Services\Backup\Databases\PostgresqlDatabase;
use App\Services\Backup\Filesystems\FilesystemProvider;
use App\Services\Backup\GzipCompressor;
use App\Services\Backup\RestoreTask;
use App\Services\Backup\ShellProcessor;
use App\Services\DatabaseConnectionTester;
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
    $this->connectionTester = Mockery::mock(DatabaseConnectionTester::class);

    // Create a partial mock of RestoreTask to mock prepareDatabase
    $this->restoreTask = Mockery::mock(
        RestoreTask::class,
        [
            $this->mysqlDatabase,
            $this->postgresqlDatabase,
            $this->shellProcessor,
            $this->filesystemProvider,
            $this->compressor,
            $this->connectionTester,
        ]
    )->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // Create temp directory for test files
    $this->tempDir = sys_get_temp_dir().'/restore-task-test-'.uniqid();
    mkdir($this->tempDir, 0777, true);
});

// Helper function to create a database server for restore tests
function createRestoreDatabaseServer(array $attributes): DatabaseServer
{
    return DatabaseServer::create($attributes);
}

// Helper function to create a snapshot for restore tests
function createRestoreSnapshot(DatabaseServer $databaseServer, array $attributes = []): Snapshot
{
    $volume = Volume::create([
        'name' => 'Test Volume',
        'type' => 'local',
        'config' => ['root' => test()->tempDir],
    ]);

    $backup = Backup::create([
        'recurrence' => 'daily',
        'volume_id' => $volume->id,
        'database_server_id' => $databaseServer->id,
    ]);

    return Snapshot::create(array_merge([
        'database_server_id' => $databaseServer->id,
        'backup_id' => $backup->id,
        'volume_id' => $volume->id,
        'path' => 'test-backup.sql.gz',
        'file_size' => 1024,
        'started_at' => now(),
        'completed_at' => now(),
        'status' => 'completed',
        'database_name' => $databaseServer->database_name ?? 'testdb',
        'database_type' => $databaseServer->database_type,
        'database_host' => $databaseServer->host,
        'database_port' => $databaseServer->port,
        'compression_type' => 'gzip',
        'method' => 'manual',
    ], $attributes));
}

// Helper function to set up common expectations for restore
function setupRestoreExpectations(
    DatabaseServer $targetServer,
    Snapshot $snapshot,
    string $schemaName,
    $databaseInterface,
    string $restoreCommand = 'mysql'
): void {
    $compressedFile = test()->tempDir.'/restore-'.uniqid().'.sql.gz';
    $decompressedFile = test()->tempDir.'/restore-'.uniqid().'.sql';
    $filesystem = Mockery::mock(Filesystem::class);

    // Connection test
    test()->connectionTester
        ->shouldReceive('test')
        ->once()
        ->andReturn(['success' => true, 'message' => 'Connected']);

    // Mock prepareDatabase to avoid real database operations
    test()->restoreTask
        ->shouldReceive('prepareDatabase')
        ->once()
        ->with($targetServer, $schemaName, Mockery::any())
        ->andReturnNull();

    // Filesystem provider for working directory
    test()->filesystemProvider
        ->shouldReceive('getConfig')
        ->with('local', 'root')
        ->andReturn(test()->tempDir);

    // Get filesystem for snapshot volume
    test()->filesystemProvider
        ->shouldReceive('get')
        ->with($snapshot->volume->type)
        ->andReturn($filesystem);

    // Download snapshot
    $filesystem
        ->shouldReceive('readStream')
        ->once()
        ->with($snapshot->path)
        ->andReturnUsing(function () use ($compressedFile) {
            file_put_contents($compressedFile, 'compressed backup data');

            return fopen($compressedFile, 'r');
        });

    // Decompression - use real command that creates the file
    test()->compressor
        ->shouldReceive('getDecompressCommandLine')
        ->once()
        ->andReturnUsing(function () use ($decompressedFile) {
            // Return a safe command that creates the decompressed file
            return sprintf('echo "decompressed backup data" > %s', escapeshellarg($decompressedFile));
        });

    test()->compressor
        ->shouldReceive('getDecompressedPath')
        ->once()
        ->andReturn($decompressedFile);

    // Database interface configuration
    $databaseInterface
        ->shouldReceive('setConfig')
        ->once()
        ->with([
            'host' => $targetServer->host,
            'port' => $targetServer->port,
            'user' => $targetServer->username,
            'pass' => $targetServer->password,
            'database' => $schemaName,
        ]);

    // Restore command - use a safe real command
    $databaseInterface
        ->shouldReceive('getRestoreCommandLine')
        ->once()
        ->andReturnUsing(function () {
            // Return a safe shell command that simulates a restore
            return 'echo "fake database restore"';
        });
}

afterEach(function () {
    // Remove temp directory and all files within
    if (is_dir($this->tempDir)) {
        // Remove any remaining files in the directory
        $files = glob($this->tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    Mockery::close();
});

test('run executes mysql restore workflow successfully', function () {
    // Arrange
    $sourceServer = createRestoreDatabaseServer([
        'name' => 'Source MySQL',
        'host' => 'source.localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => 'sourcedb',
    ]);

    $targetServer = createRestoreDatabaseServer([
        'name' => 'Target MySQL',
        'host' => 'target.localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => 'targetdb',
    ]);

    $snapshot = createRestoreSnapshot($sourceServer);

    setupRestoreExpectations($targetServer, $snapshot, 'restored_db', $this->mysqlDatabase, 'mysql restored_db');

    // Act
    $this->restoreTask->run($targetServer, $snapshot, 'restored_db');

    // Assert
    expect(true)->toBeTrue();
});

test('run executes postgresql restore workflow successfully', function () {
    // Arrange
    $sourceServer = createRestoreDatabaseServer([
        'name' => 'Source PostgreSQL',
        'host' => 'source.localhost',
        'port' => 5432,
        'database_type' => 'postgresql',
        'username' => 'postgres',
        'password' => 'secret',
        'database_name' => 'sourcedb',
    ]);

    $targetServer = createRestoreDatabaseServer([
        'name' => 'Target PostgreSQL',
        'host' => 'target.localhost',
        'port' => 5432,
        'database_type' => 'postgresql',
        'username' => 'postgres',
        'password' => 'secret',
        'database_name' => 'targetdb',
    ]);

    $snapshot = createRestoreSnapshot($sourceServer, ['database_type' => 'postgresql']);

    setupRestoreExpectations($targetServer, $snapshot, 'restored_db', $this->postgresqlDatabase, 'psql restored_db');

    // Act
    $this->restoreTask->run($targetServer, $snapshot, 'restored_db');

    // Assert
    expect(true)->toBeTrue();
});

test('run throws exception when database types are incompatible', function () {
    // Arrange
    $sourceServer = createRestoreDatabaseServer([
        'name' => 'Source MySQL',
        'host' => 'localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => 'sourcedb',
    ]);

    $targetServer = createRestoreDatabaseServer([
        'name' => 'Target PostgreSQL',
        'host' => 'localhost',
        'port' => 5432,
        'database_type' => 'postgresql',
        'username' => 'postgres',
        'password' => 'secret',
        'database_name' => 'targetdb',
    ]);

    $snapshot = createRestoreSnapshot($sourceServer);

    // Act & Assert
    expect(fn () => $this->restoreTask->run($targetServer, $snapshot, 'restored_db'))
        ->toThrow(\Exception::class, 'Cannot restore mysql snapshot to postgresql server');
});

test('run throws exception when connection test fails', function () {
    // Arrange
    $sourceServer = createRestoreDatabaseServer([
        'name' => 'Source MySQL',
        'host' => 'localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'secret',
        'database_name' => 'sourcedb',
    ]);

    $targetServer = createRestoreDatabaseServer([
        'name' => 'Target MySQL',
        'host' => 'localhost',
        'port' => 3306,
        'database_type' => 'mysql',
        'username' => 'root',
        'password' => 'wrongpassword',
        'database_name' => 'targetdb',
    ]);

    $snapshot = createRestoreSnapshot($sourceServer);

    // Connection test fails
    $this->connectionTester
        ->shouldReceive('test')
        ->once()
        ->andReturn(['success' => false, 'message' => 'Access denied']);

    // Act & Assert
    expect(fn () => $this->restoreTask->run($targetServer, $snapshot, 'restored_db'))
        ->toThrow(\Exception::class, 'Failed to connect to target server: Access denied');
});

test('run throws exception for unsupported database type', function () {
    // Arrange
    $sourceServer = createRestoreDatabaseServer([
        'name' => 'Source Oracle',
        'host' => 'localhost',
        'port' => 1521,
        'database_type' => 'oracle',
        'username' => 'system',
        'password' => 'secret',
        'database_name' => 'sourcedb',
    ]);

    $targetServer = createRestoreDatabaseServer([
        'name' => 'Target Oracle',
        'host' => 'localhost',
        'port' => 1521,
        'database_type' => 'oracle',
        'username' => 'system',
        'password' => 'secret',
        'database_name' => 'targetdb',
    ]);

    $snapshot = createRestoreSnapshot($sourceServer, ['database_type' => 'oracle']);

    // Connection test succeeds but database type is not supported
    $this->connectionTester
        ->shouldReceive('test')
        ->once()
        ->andReturn(['success' => true, 'message' => 'Connected']);

    $this->filesystemProvider
        ->shouldReceive('getConfig')
        ->with('local', 'root')
        ->andReturn($this->tempDir);

    $filesystem = Mockery::mock(Filesystem::class);
    $this->filesystemProvider
        ->shouldReceive('get')
        ->andReturn($filesystem);

    $filesystem
        ->shouldReceive('readStream')
        ->andReturn(fopen('php://memory', 'r'));

    $decompressedFile = $this->tempDir.'/test.sql';
    $this->compressor
        ->shouldReceive('getDecompressCommandLine')
        ->andReturnUsing(function () use ($decompressedFile) {
            // Create the file with a real command
            return sprintf('echo "test data" > %s', escapeshellarg($decompressedFile));
        });

    $this->compressor
        ->shouldReceive('getDecompressedPath')
        ->andReturn($decompressedFile);

    // Act & Assert
    expect(fn () => $this->restoreTask->run($targetServer, $snapshot, 'restored_db'))
        ->toThrow(\Exception::class, 'Database type oracle not supported');
});

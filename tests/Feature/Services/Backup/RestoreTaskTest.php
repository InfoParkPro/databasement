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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TestShellProcessor;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Use REAL services for command building
    $this->mysqlDatabase = new MysqlDatabase;  // ✓ Real command building
    $this->postgresqlDatabase = new PostgresqlDatabase;  // ✓ Real command building
    $this->shellProcessor = new TestShellProcessor;  // ✓ Captures commands without executing
    $this->compressor = new GzipCompressor($this->shellProcessor);  // ✓ Real path manipulation

    // Mock external dependencies only
    $this->filesystemProvider = Mockery::mock(FilesystemProvider::class);

    // Create a partial mock of RestoreTask to mock prepareDatabase
    $this->restoreTask = Mockery::mock(
        RestoreTask::class,
        [
            $this->mysqlDatabase,
            $this->postgresqlDatabase,
            $this->shellProcessor,
            $this->filesystemProvider,
            $this->compressor,
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

    // Create BackupJob first (required for snapshot)
    $job = \App\Models\BackupJob::create([
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    return Snapshot::create(array_merge([
        'backup_job_id' => $job->id,
        'database_server_id' => $databaseServer->id,
        'backup_id' => $backup->id,
        'volume_id' => $volume->id,
        'path' => 'test-backup.sql.gz',
        'file_size' => 1024,
        'started_at' => now(),
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
    string $schemaName
): void {

    // Mock prepareDatabase to avoid real database operations
    test()->restoreTask
        ->shouldReceive('prepareDatabase')
        ->once()
        ->with($targetServer, $schemaName, Mockery::any())
        ->andReturnNull();

    // Mock download - create a compressed file that will be decompressed
    test()->filesystemProvider
        ->shouldReceive('download')
        ->once()
        ->with($snapshot, Mockery::any())
        ->andReturnUsing(function ($snap, $destination) {
            file_put_contents($destination, 'compressed backup data');
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

    // Create snapshot with a known path
    $snapshot = createRestoreSnapshot($sourceServer, ['path' => 'backup.sql.gz']);

    setupRestoreExpectations($targetServer, $snapshot, 'restored_db');

    // Act
    $this->restoreTask->run($targetServer, $snapshot, 'restored_db', $this->tempDir);

    // Build expected file paths
    $compressedFile = $this->tempDir.'/backup.sql.gz';
    $decompressedFile = $this->tempDir.'/backup.sql';

    // Expected commands
    $expectedCommands = [
        "gzip -d '$compressedFile'",
        "mariadb --host='target.localhost' --port='3306' --user='root' --password='secret' --skip_ssl 'restored_db' -e \"source $decompressedFile\"",
    ];

    $commands = $this->shellProcessor->getCommands();
    expect($commands)->toEqual($expectedCommands);
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

    // Create snapshot with a known path
    $snapshot = createRestoreSnapshot($sourceServer, [
        'database_type' => 'postgresql',
        'path' => 'pg_backup.sql.gz',
    ]);

    setupRestoreExpectations($targetServer, $snapshot, 'restored_db');

    // Act
    $this->restoreTask->run($targetServer, $snapshot, 'restored_db', $this->tempDir);

    // Build expected file paths
    $compressedFile = $this->tempDir.'/pg_backup.sql.gz';
    $decompressedFile = $this->tempDir.'/pg_backup.sql';

    // Expected commands (PostgreSQL uses escapeshellarg on paths, adding quotes)
    $expectedCommands = [
        "gzip -d '$compressedFile'",
        "PGPASSWORD='secret' psql --host='target.localhost' --port='5432' --user='postgres' 'restored_db' -f '$decompressedFile'",
    ];

    $commands = $this->shellProcessor->getCommands();
    expect($commands)->toEqual($expectedCommands);
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

test('run throws exception when restore command failed', function () {
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

    $snapshot = createRestoreSnapshot($sourceServer, ['path' => 'backup.sql.gz']);

    // Create a shell processor that fails on restore command (the second call after decompress)
    $shellProcessor = Mockery::mock(\App\Services\Backup\ShellProcessor::class);
    $shellProcessor->shouldReceive('setLogger')->once();
    $shellProcessor->shouldReceive('process')
        ->once()
        ->andThrow(new \App\Exceptions\ShellProcessFailed('Access denied for user'));

    // Mock compressor to skip decompression and simulate decompressed file
    $compressor = Mockery::mock(\App\Services\Backup\GzipCompressor::class);
    $compressor->shouldReceive('decompress')
        ->once()
        ->andReturnUsing(function ($compressedFile) {
            $decompressedFile = preg_replace('/\.gz$/', '', $compressedFile);
            file_put_contents($decompressedFile, "-- Fake decompressed data\n");

            return $decompressedFile;
        });

    // Recreate RestoreTask with mocked shell processor and compressor
    $restoreTask = Mockery::mock(
        \App\Services\Backup\RestoreTask::class,
        [
            $this->mysqlDatabase,
            $this->postgresqlDatabase,
            $shellProcessor,
            $this->filesystemProvider,
            $compressor,
        ]
    )->makePartial()
        ->shouldAllowMockingProtectedMethods();

    // Mock prepareDatabase
    $restoreTask->shouldAllowMockingProtectedMethods();
    $restoreTask
        ->shouldReceive('prepareDatabase')
        ->once()
        ->andReturnNull();

    // Mock download
    $this->filesystemProvider
        ->shouldReceive('download')
        ->once()
        ->andReturnUsing(function ($snap, $destination) {
            file_put_contents($destination, 'compressed backup data');
        });

    // Count jobs before to find the new one created during restore
    $jobCountBefore = \App\Models\BackupJob::count();

    // Act & Assert
    $exception = null;
    try {
        $restoreTask->run($targetServer, $snapshot, 'restored_db', $this->tempDir);
    } catch (\App\Exceptions\ShellProcessFailed $e) {
        $exception = $e;
    }

    expect($exception)->not->toBeNull();
    expect($exception->getMessage())->toBe('Access denied for user');

    // Verify the job status is set to failed
    // Get the restore job (should have a restore relationship)
    $restore = \App\Models\Restore::whereSnapshotId($snapshot->id)->first();
    $job = $restore->job;

    // Ensure we got the new job
    expect(\App\Models\BackupJob::count())->toBe($jobCountBefore + 1);
    expect($job)->not->toBeNull();
    expect($job->status)->toBe('failed');
    expect($job->error_message)->toBe('Access denied for user');
    expect($job->completed_at)->not->toBeNull();
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

    $this->filesystemProvider
        ->shouldReceive('download')
        ->once()
        ->andReturnUsing(function ($snap, $destination) {
            file_put_contents($destination, 'compressed test data');
        });

    // Act & Assert
    expect(fn () => $this->restoreTask->run($targetServer, $snapshot, 'restored_db', $this->tempDir))
        ->toThrow(\Exception::class, 'Database type oracle not supported');
});

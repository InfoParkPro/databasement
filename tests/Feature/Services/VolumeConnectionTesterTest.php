<?php

use App\Models\Volume;
use App\Services\Backup\Filesystems\FilesystemProvider;
use App\Services\VolumeConnectionTester;
use App\Support\FilesystemSupport;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToWriteFile;

beforeEach(function () {
    $this->tester = app(VolumeConnectionTester::class);
});

describe('local volume connection testing', function () {
    test('returns success for valid writable directory', function () {
        $volume = Volume::factory()->local()->create();
        $tempDir = $volume->config['path'];

        $result = $this->tester->test($volume);

        expect($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('Connection successful');

        // Cleanup
        FilesystemSupport::cleanupDirectory($tempDir);
    });

    test('creates and removes test file during validation', function () {
        $volume = Volume::factory()->local()->create();
        $tempDir = $volume->config['path'];

        expect(glob($tempDir.'/*'))->toBeEmpty();

        $result = $this->tester->test($volume);

        // After test, directory should still be empty (test file cleaned up)
        expect(glob($tempDir.'/*'))->toBeEmpty()
            ->and($result['success'])->toBeTrue();

        // Cleanup
        FilesystemSupport::cleanupDirectory($tempDir);
    });

    test('returns error when directory does not exist and cannot be created', function () {
        $volume = new Volume([
            'name' => 'test-volume',
            'type' => 'local',
            'config' => ['path' => '/nonexistent-root-'.uniqid().'/subdir'],
        ]);

        $result = $this->tester->test($volume);

        expect($result['success'])->toBeFalse();
    });

    test('returns error for unsupported volume type', function () {
        $volume = new Volume([
            'name' => 'test-volume',
            'type' => 'unknown',
            'config' => [],
        ]);

        $result = $this->tester->test($volume);

        expect($result['success'])->toBeFalse();
    });
});

describe('S3 volume connection testing', function () {
    test('returns success when S3 filesystem write/read/delete succeeds', function () {
        $volume = new Volume([
            'name' => 'test-s3-volume',
            'type' => 's3',
            'config' => [
                'bucket' => 'test-bucket',
                'prefix' => '',
            ],
        ]);

        // Capture the content written so we can return it on read
        $capturedContent = null;
        $mockFilesystem = Mockery::mock(Filesystem::class);
        $mockFilesystem->shouldReceive('write')
            ->once()
            ->withArgs(function ($filename, $content) use (&$capturedContent) {
                $capturedContent = $content;

                return str_starts_with($filename, '.databasement-test-');
            });
        $mockFilesystem->shouldReceive('read')
            ->once()
            ->andReturnUsing(function () use (&$capturedContent) {
                return $capturedContent;
            });
        $mockFilesystem->shouldReceive('delete')->once();

        // Mock FilesystemProvider to return our mock filesystem
        $mockProvider = Mockery::mock(FilesystemProvider::class);
        $mockProvider->shouldReceive('getForVolume')
            ->once()
            ->with(Mockery::on(fn ($v) => $v->type === 's3' && $v->config['bucket'] === 'test-bucket'))
            ->andReturn($mockFilesystem);

        $tester = new VolumeConnectionTester($mockProvider);
        $result = $tester->test($volume);

        expect($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('Connection successful');
    });

    test('returns error when S3 filesystem write fails', function () {
        $volume = new Volume([
            'name' => 'test-s3-volume',
            'type' => 's3',
            'config' => [
                'bucket' => 'non-existent-bucket',
                'prefix' => '',
            ],
        ]);

        // Mock the filesystem to throw an exception
        $mockFilesystem = Mockery::mock(Filesystem::class);
        $mockFilesystem->shouldReceive('write')
            ->once()
            ->andThrow(UnableToWriteFile::atLocation('.databasement-test-123', 'Bucket does not exist'));

        // Mock FilesystemProvider
        $mockProvider = Mockery::mock(FilesystemProvider::class);
        $mockProvider->shouldReceive('getForVolume')
            ->once()
            ->andReturn($mockFilesystem);

        $tester = new VolumeConnectionTester($mockProvider);
        $result = $tester->test($volume);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Bucket does not exist');
    });

    test('returns error when S3 filesystem read returns different content', function () {
        $volume = new Volume([
            'name' => 'test-s3-volume',
            'type' => 's3',
            'config' => [
                'bucket' => 'test-bucket',
                'prefix' => '',
            ],
        ]);

        // Mock the filesystem to return different content
        $mockFilesystem = Mockery::mock(Filesystem::class);
        $mockFilesystem->shouldReceive('write')->once();
        $mockFilesystem->shouldReceive('read')
            ->once()
            ->andReturn('different-content');
        $mockFilesystem->shouldReceive('delete')->once();

        // Mock FilesystemProvider
        $mockProvider = Mockery::mock(FilesystemProvider::class);
        $mockProvider->shouldReceive('getForVolume')
            ->once()
            ->andReturn($mockFilesystem);

        $tester = new VolumeConnectionTester($mockProvider);
        $result = $tester->test($volume);

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Failed to verify test file content');
    });
});

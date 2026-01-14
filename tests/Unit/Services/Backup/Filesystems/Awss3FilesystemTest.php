<?php

use App\Services\Backup\Filesystems\Awss3Filesystem;

test('buildKeyPath includes prefix in key path', function () {
    $filesystem = new Awss3Filesystem;

    $config = [
        'bucket' => 'test-bucket',
        'prefix' => 'prefix/path',
    ];

    $key = $filesystem->buildKeyPath($config, 'file.sql.gz');

    expect($key)->toBe('prefix/path/file.sql.gz');
});

test('buildKeyPath handles prefix with trailing slash', function () {
    $filesystem = new Awss3Filesystem;

    $config = [
        'bucket' => 'test-bucket',
        'prefix' => 'prefix/',
    ];

    $key = $filesystem->buildKeyPath($config, 'file.sql.gz');

    // Should not have double slashes
    expect($key)->toBe('prefix/file.sql.gz');
});

test('buildKeyPath works without prefix', function () {
    $filesystem = new Awss3Filesystem;

    $config = [
        'bucket' => 'test-bucket',
        // No prefix
    ];

    $key = $filesystem->buildKeyPath($config, 'file.sql.gz');

    expect($key)->toBe('file.sql.gz');
});

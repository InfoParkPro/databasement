<?php

namespace App\Services\Backup\Filesystems;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystem implements FilesystemInterface
{
    public function handles($type): bool
    {
        return strtolower($type ?? '') == 'local';
    }

    public function get(array $config): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter($config['root']));
    }
}

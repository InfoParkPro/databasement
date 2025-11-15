<?php

namespace App\Services\Backup\Filesystems;

use League\Flysystem\Filesystem;

interface FilesystemInterface
{
    public function handles($type): bool;

    public function get(array $config): Filesystem;
}

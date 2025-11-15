<?php

namespace App\Services\Backup\Databases;

interface DatabaseInterface
{
    public function handles($type): bool;

    public function setConfig(array $config): void;

    public function getDumpCommandLine($outputPath): string;

    public function getRestoreCommandLine($inputPath): string;
}

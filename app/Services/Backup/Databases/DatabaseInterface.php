<?php

namespace App\Services\Backup\Databases;

interface DatabaseInterface
{
    public function handles(mixed $type): bool;

    /**
     * @param  array<string, mixed>  $config
     */
    public function setConfig(array $config): void;

    public function getDumpCommandLine(string $outputPath): string;

    public function getRestoreCommandLine(string $inputPath): string;
}

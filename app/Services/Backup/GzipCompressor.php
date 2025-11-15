<?php

namespace App\Services\Backup;

class GzipCompressor
{
    public function getCompressCommandLine($inputPath)
    {
        return 'gzip '.escapeshellarg($inputPath);
    }

    public function getDecompressCommandLine($outputPath)
    {
        return 'gzip -d '.escapeshellarg($outputPath);
    }

    public function getCompressedPath($inputPath)
    {
        return $inputPath.'.gz';
    }

    public function getDecompressedPath($inputPath)
    {
        return preg_replace('/\.gz$/', '', $inputPath);
    }
}

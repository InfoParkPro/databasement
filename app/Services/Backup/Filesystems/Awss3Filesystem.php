<?php

namespace App\Services\Backup\Filesystems;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

class Awss3Filesystem implements FilesystemInterface
{
    private ?S3Client $client = null;

    public function handles(?string $type): bool
    {
        return in_array(strtolower($type ?? ''), ['s3', 'awss3']);
    }

    public function get(array $config): Filesystem
    {
        $client = $this->getClient();

        // Support both 'root' (from config/backup.php) and 'prefix' (from Volume database)
        $root = $config['root'] ?? $config['prefix'] ?? '';

        return new Filesystem(new AwsS3V3Adapter($client, $config['bucket'], $root));
    }

    /**
     * Generate a presigned URL for downloading a file from S3
     *
     * @param  array<string, mixed>  $config
     */
    public function getPresignedUrl(array $config, string $path, int $expiresInMinutes = 60): string
    {
        $client = $this->getClient();

        $command = $client->getCommand('GetObject', [
            'Bucket' => $config['bucket'],
            'Key' => $path,
        ]);

        $request = $client->createPresignedRequest($command, "+{$expiresInMinutes} minutes");

        return (string) $request->getUri();
    }

    private function getClient(): S3Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $this->client = $this->createClient();

        return $this->client;
    }

    private function createClient(): S3Client
    {
        $awsConfig = config('services.aws');
        $clientConfig = ['version' => 'latest'];

        if (! empty($awsConfig['key']) && ! empty($awsConfig['secret'])) {
            $clientConfig['credentials'] = [
                'key' => $awsConfig['key'],
                'secret' => $awsConfig['secret'],
            ];
        }

        if (! empty($awsConfig['region'])) {
            $clientConfig['region'] = $awsConfig['region'];
        }

        if (! empty($awsConfig['endpoint'])) {
            $clientConfig['endpoint'] = $awsConfig['endpoint'];
        }

        if ($awsConfig['use_path_style_endpoint']) {
            $clientConfig['use_path_style_endpoint'] = true;
        }

        return new S3Client($clientConfig);
    }
}

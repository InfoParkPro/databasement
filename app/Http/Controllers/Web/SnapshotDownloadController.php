<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Snapshot;
use App\Services\Backup\Filesystems\Awss3Filesystem;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SnapshotDownloadController extends Controller
{
    public function __invoke(Snapshot $snapshot): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('download', $snapshot);

        $snapshot->loadMissing('volume');

        if ($snapshot->volume->type === 's3') {
            $s3Filesystem = app(Awss3Filesystem::class);
            $presignedUrl = $s3Filesystem->getPresignedUrl(
                $snapshot->volume->getDecryptedConfig(),
                $snapshot->filename,
                expiresInMinutes: 15
            );

            return redirect()->away($presignedUrl);
        }

        if ($snapshot->volume->type !== 'local') {
            abort(422, 'Unsupported storage type.');
        }

        $volumeRoot = $snapshot->volume->config['path'] ?? $snapshot->volume->config['root'] ?? '';
        $fullPath = rtrim((string) $volumeRoot, '/').'/'.$snapshot->filename;

        if (! is_file($fullPath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($fullPath, basename($snapshot->filename), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}

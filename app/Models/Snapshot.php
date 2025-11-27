<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use League\Flysystem\Filesystem;

/**
 * @property string $id
 * @property string $database_server_id
 * @property string $backup_id
 * @property string $volume_id
 * @property string $path
 * @property int $file_size
 * @property string|null $checksum
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string $status
 * @property string|null $error_message
 * @property string|null $error_trace
 * @property string $database_name
 * @property string $database_type
 * @property string $database_host
 * @property int $database_port
 * @property int|null $database_size_bytes
 * @property string $compression_type
 * @property string $method
 * @property string|null $triggered_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DatabaseServer $databaseServer
 * @property-read \App\Models\Backup $backup
 * @property-read \App\Models\Volume $volume
 * @property-read \App\Models\User|null $triggeredBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Snapshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Snapshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Snapshot query()
 *
 * @mixin \Eloquent
 */
class Snapshot extends Model
{
    use HasUlids;

    protected $fillable = [
        'database_server_id',
        'backup_id',
        'volume_id',
        'job_id',
        'path',
        'file_size',
        'checksum',
        'started_at',
        'completed_at',
        'status',
        'error_message',
        'error_trace',
        'database_name',
        'database_type',
        'database_host',
        'database_port',
        'database_size_bytes',
        'compression_type',
        'method',
        'triggered_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'file_size' => 'integer',
            'database_port' => 'integer',
            'database_size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Delete the backup file when snapshot is deleted
        static::deleting(function (Snapshot $snapshot) {
            $snapshot->deleteBackupFile();
        });
    }

    public function databaseServer(): BelongsTo
    {
        return $this->belongsTo(DatabaseServer::class);
    }

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }

    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /**
     * Calculate the duration of the backup in milliseconds
     */
    public function getDurationMs(): ?int
    {
        if ($this->completed_at === null) {
            return null;
        }

        return (int) $this->started_at->diffInMilliseconds($this->completed_at);
    }

    /**
     * Get human-readable duration
     */
    public function getHumanDuration(): ?string
    {
        $ms = $this->getDurationMs();

        if ($ms === null) {
            return null;
        }

        if ($ms < 1000) {
            return "{$ms}ms";
        }

        $seconds = round($ms / 1000, 2);

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = round($seconds % 60, 2);

        return "{$minutes}m {$remainingSeconds}s";
    }

    /**
     * Get human-readable file size
     */
    public function getHumanFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get human-readable database size
     */
    public function getHumanDatabaseSize(): ?string
    {
        if ($this->database_size_bytes === null) {
            return null;
        }

        $bytes = $this->database_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Delete the backup file from the volume
     */
    public function deleteBackupFile(): bool
    {
        try {
            // Get the filesystem for this volume
            $filesystemProvider = app(\App\Services\Backup\Filesystems\FilesystemProvider::class);
            $filesystem = $filesystemProvider->get($this->volume->type);

            // Delete the file if it exists
            if ($filesystem->fileExists($this->path)) {
                $filesystem->delete($this->path);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            // Log the error but don't throw to prevent deletion cascade failure
            logger()->error('Failed to delete backup file for snapshot', [
                'snapshot_id' => $this->id,
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mark snapshot as completed
     */
    public function markCompleted(?string $checksum = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'checksum' => $checksum,
        ]);
    }

    /**
     * Mark snapshot as failed
     */
    public function markFailed(\Throwable $exception): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $exception->getMessage(),
            'error_trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Scope to filter by status
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter by status
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter by database server
     */
    public function scopeForDatabaseServer($query, DatabaseServer $databaseServer)
    {
        return $query->where('database_server_id', $databaseServer->id);
    }
}

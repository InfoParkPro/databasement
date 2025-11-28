<?php

namespace App\Models;

use App\Contracts\JobInterface;
use App\Models\Concerns\HasJob;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $snapshot_id
 * @property string $target_server_id
 * @property string $schema_name
 * @property string|null $job_id
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string $status
 * @property string|null $error_message
 * @property string|null $error_trace
 * @property string|null $triggered_by_user_id
 * @property array|null $logs
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Snapshot $snapshot
 * @property-read \App\Models\DatabaseServer $targetServer
 * @property-read \App\Models\User|null $triggeredBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Restore newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Restore newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Restore query()
 *
 * @mixin \Eloquent
 */
class Restore extends Model implements JobInterface
{
    use HasJob;
    use HasUlids;

    protected $fillable = [
        'snapshot_id',
        'target_server_id',
        'schema_name',
        'job_id',
        'started_at',
        'completed_at',
        'status',
        'error_message',
        'error_trace',
        'triggered_by_user_id',
        'logs',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'logs' => 'array',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(Snapshot::class);
    }

    public function targetServer(): BelongsTo
    {
        return $this->belongsTo(DatabaseServer::class, 'target_server_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }
}

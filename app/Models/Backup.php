<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $database_server_id
 * @property string $volume_id
 * @property array<string>|null $volume_ids
 * @property string|null $path
 * @property string $backup_schedule_id
 * @property int|null $retention_days
 * @property string $retention_policy
 * @property int|null $gfs_keep_daily
 * @property int|null $gfs_keep_weekly
 * @property int|null $gfs_keep_monthly
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DatabaseServer $databaseServer
 * @property-read BackupSchedule $backupSchedule
 * @property-read Collection<int, Snapshot> $snapshots
 * @property-read int|null $snapshots_count
 * @property-read Volume $volume
 *
 * @method static Builder<static>|Backup newModelQuery()
 * @method static Builder<static>|Backup newQuery()
 * @method static Builder<static>|Backup query()
 *
 * @mixin \Eloquent
 */
class Backup extends Model
{
    use HasUlids;

    public const string RETENTION_DAYS = 'days';

    public const string RETENTION_GFS = 'gfs';

    public const string RETENTION_FOREVER = 'forever';

    public const array RETENTION_POLICIES = [
        self::RETENTION_DAYS,
        self::RETENTION_GFS,
        self::RETENTION_FOREVER,
    ];

    protected $fillable = [
        'database_server_id',
        'volume_id',
        'volume_ids',
        'path',
        'backup_schedule_id',
        'retention_days',
        'retention_policy',
        'gfs_keep_daily',
        'gfs_keep_weekly',
        'gfs_keep_monthly',
    ];

    protected function casts(): array
    {
        return [
            'volume_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<DatabaseServer, Backup>
     */
    public function databaseServer(): BelongsTo
    {
        return $this->belongsTo(DatabaseServer::class);
    }

    /**
     * @return BelongsTo<Volume, Backup>
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }

    /**
     * @return BelongsTo<BackupSchedule, Backup>
     */
    public function backupSchedule(): BelongsTo
    {
        return $this->belongsTo(BackupSchedule::class);
    }

    /**
     * @return HasMany<Snapshot, Backup>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    /**
     * Return normalized target volume IDs, with fallback to legacy volume_id.
     *
     * @return array<string>
     */
    public function getEffectiveVolumeIds(): array
    {
        $ids = array_values(array_filter(
            array_map(
                static fn ($id) => is_string($id) ? trim($id) : '',
                $this->volume_ids ?? []
            ),
            static fn (string $id) => $id !== ''
        ));

        if ($ids !== []) {
            return array_values(array_unique($ids));
        }

        return $this->volume_id !== '' ? [$this->volume_id] : [];
    }
}

<?php

namespace App\Services\Backup;

use App\Enums\CompressionType;
use App\Enums\DatabaseType;
use App\Facades\AppConfig;
use App\Models\BackupJob;
use App\Models\DatabaseServer;
use App\Models\Restore;
use App\Models\Snapshot;
use App\Models\Volume;
use App\Services\Backup\Databases\DatabaseProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BackupJobFactory
{
    public function __construct(
        protected DatabaseProvider $databaseProvider
    ) {}

    /**
     * Create backup job(s) for a database server.
     *
     * For selected mode: Returns array with one Snapshot per selected database
     * For all mode: Returns array with Snapshot per database on server
     * For pattern mode: Returns array with Snapshot per matching database
     * For SQLite: Returns array with one Snapshot per configured path
     *
     * @param  'manual'|'scheduled'  $method
     * @return Snapshot[]
     */
    public function createSnapshots(
        DatabaseServer $server,
        string $method,
        ?int $triggeredByUserId = null
    ): array {
        $snapshots = [];
        $targetVolumes = $this->resolveTargetVolumes($server);

        if ($targetVolumes->isEmpty()) {
            Log::warning("No target volumes found for server [{$server->name}] backup configuration.");

            return $snapshots;
        }

        // SQLite: one snapshot per database file path
        if ($server->database_type === DatabaseType::SQLITE) {
            foreach ($server->database_names ?? [] as $databasePath) {
                foreach ($targetVolumes as $volume) {
                    $snapshots[] = $this->createSnapshot($server, $databasePath, $volume, $method, $triggeredByUserId);
                }
            }

            return $snapshots;
        }

        // Redis: single snapshot, dumps entire instance
        if ($server->database_type === DatabaseType::REDIS) {
            foreach ($targetVolumes as $volume) {
                $snapshots[] = $this->createSnapshot($server, 'all', $volume, $method, $triggeredByUserId);
            }

            return $snapshots;
        }

        $databases = match ($server->database_selection_mode) {
            'all' => $this->databaseProvider->listDatabasesForServer($server),
            'pattern' => DatabaseServer::filterDatabasesByPattern(
                $this->databaseProvider->listDatabasesForServer($server),
                $server->database_include_pattern ?? ''
            ),
            default => $server->database_names ?? [],
        };

        if (empty($databases)) {
            Log::warning("No databases found on server [{$server->name}] to backup.");
        }

        foreach ($databases as $databaseName) {
            foreach ($targetVolumes as $volume) {
                $snapshots[] = $this->createSnapshot($server, $databaseName, $volume, $method, $triggeredByUserId);
            }
        }

        return $snapshots;
    }

    /**
     * Create a single snapshot for one database.
     *
     * @param  'manual'|'scheduled'  $method
     */
    protected function createSnapshot(
        DatabaseServer $server,
        string $databaseName,
        Volume $volume,
        string $method,
        ?int $triggeredByUserId = null
    ): Snapshot {
        $job = BackupJob::create(['status' => 'pending']);

        $snapshot = Snapshot::create([
            'backup_job_id' => $job->id,
            'database_server_id' => $server->id,
            'backup_id' => $server->backup->id,
            'volume_id' => $volume->id,
            'filename' => '',
            'file_size' => 0,
            'checksum' => null,
            'started_at' => now(),
            'database_name' => $databaseName,
            'database_type' => $server->database_type,
            'compression_type' => CompressionType::from(AppConfig::get('backup.compression')),
            'method' => $method,
            'metadata' => Snapshot::generateMetadata($server, $databaseName, $volume),
            'triggered_by_user_id' => $triggeredByUserId,
        ]);

        $snapshot->load(['job', 'volume', 'databaseServer']);

        return $snapshot;
    }

    /**
     * Resolve configured target volumes for snapshot fan-out.
     *
     * @return Collection<int, Volume>
     */
    protected function resolveTargetVolumes(DatabaseServer $server): Collection
    {
        $volumeIds = $server->backup->getEffectiveVolumeIds();
        if ($volumeIds === []) {
            return collect();
        }

        $volumes = Volume::query()
            ->whereIn('id', $volumeIds)
            ->get()
            ->keyBy('id');

        $orderedVolumes = collect();
        foreach ($volumeIds as $volumeId) {
            if ($volumes->has($volumeId)) {
                $orderedVolumes->push($volumes->get($volumeId));
                continue;
            }

            Log::warning("Configured backup volume [{$volumeId}] was not found for server [{$server->name}].");
        }

        return $orderedVolumes;
    }

    /**
     * Create a BackupJob and Restore for a snapshot restore operation.
     */
    public function createRestore(
        Snapshot $snapshot,
        DatabaseServer $targetServer,
        string $schemaName,
        ?int $triggeredByUserId = null
    ): Restore {
        $job = BackupJob::create(['status' => 'pending']);

        $restore = Restore::create([
            'backup_job_id' => $job->id,
            'snapshot_id' => $snapshot->id,
            'target_server_id' => $targetServer->id,
            'schema_name' => $schemaName,
            'triggered_by_user_id' => $triggeredByUserId,
        ]);

        $restore->load(['job', 'snapshot.volume', 'snapshot.databaseServer', 'targetServer']);

        return $restore;
    }
}

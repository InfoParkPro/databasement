<?php

namespace App\Services\Backup;

use App\Jobs\ProcessBackupJob;
use App\Models\DatabaseServer;
use App\Models\Snapshot;
use RuntimeException;

class TriggerBackupAction
{
    public function __construct(
        private BackupJobFactory $backupJobFactory
    ) {}

    /**
     * Trigger a backup for the given database server.
     *
     * @return array{snapshots: Snapshot[], message: string}
     *
     * @throws RuntimeException
     */
    public function execute(DatabaseServer $server, ?int $triggeredByUserId = null): array
    {
        if (! $server->backup) {
            throw new RuntimeException(
                'No backup configuration found for this database server.'
            );
        }

        $snapshots = $this->backupJobFactory->createSnapshots(
            server: $server,
            method: 'manual',
            triggeredByUserId: $triggeredByUserId
        );

        foreach ($snapshots as $snapshot) {
            ProcessBackupJob::dispatch($snapshot->id);
        }

        $count = count($snapshots);
        $message = $count === 1
            ? 'Backup started successfully!'
            : "{$count} database backups started successfully!";

        return [
            'snapshots' => $snapshots,
            'message' => $message,
        ];
    }
}

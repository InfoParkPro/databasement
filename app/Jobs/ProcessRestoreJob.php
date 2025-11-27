<?php

namespace App\Jobs;

use App\Models\Restore;
use App\Services\Backup\RestoreTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRestoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * Set to 1 (no retries) because restore operations might have already
     * partially modified the target database.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $restoreId
    ) {
        $this->onQueue('backups');
    }

    /**
     * Execute the job.
     */
    public function handle(RestoreTask $restoreTask): void
    {
        // Fetch the restore record with relationships
        $restore = Restore::with(['snapshot.volume', 'targetServer'])->findOrFail($this->restoreId);

        // Update restore with job ID and mark as running
        $restore->update(['job_id' => $this->job->getJobId()]);
        $restore->markRunning();

        try {
            // Run the restore task
            $restoreTask->run(
                $restore->targetServer,
                $restore->snapshot,
                $restore->schema_name
            );

            // Mark as completed
            $restore->markCompleted();

            Log::info('Restore completed successfully', [
                'restore_id' => $this->restoreId,
                'snapshot_id' => $restore->snapshot_id,
                'target_server_id' => $restore->target_server_id,
                'schema_name' => $restore->schema_name,
            ]);
        } catch (\Throwable $exception) {
            // Mark as failed
            $restore->markFailed($exception);

            Log::error('Restore job failed', [
                'restore_id' => $this->restoreId,
                'snapshot_id' => $restore->snapshot_id,
                'target_server_id' => $restore->target_server_id,
                'schema_name' => $restore->schema_name,
                'error' => $exception->getMessage(),
            ]);

            // Re-throw to mark job as failed in queue
            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Restore job failed permanently', [
            'restore_id' => $this->restoreId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Ensure restore is marked as failed
        $restore = Restore::find($this->restoreId);
        if ($restore && $restore->status !== 'failed') {
            $restore->markFailed($exception);
        }
    }
}

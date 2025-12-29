<?php

namespace App\Jobs;

use App\Models\Restore;
use App\Services\Backup\RestoreTask;
use App\Support\FilesystemSupport;
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
    public int $timeout = 7200; // 2 hour

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Working directory for temporary files.
     */
    private string $workingDirectory;

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
        $restore = Restore::with(['job', 'snapshot.volume', 'snapshot.databaseServer', 'targetServer'])
            ->findOrFail($this->restoreId);

        // Update job with queue job ID for tracking
        $restore->job->update(['job_id' => $this->job->getJobId()]);

        // Create unique working directory for this job
        $this->workingDirectory = FilesystemSupport::createWorkingDirectory('restore', $this->restoreId);

        // Run the restore task
        $restoreTask->run($restore, $this->workingDirectory);

        Log::info('Restore completed successfully', [
            'restore_id' => $this->restoreId,
            'snapshot_id' => $restore->snapshot_id,
            'target_server_id' => $restore->target_server_id,
            'schema_name' => $restore->schema_name,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Restore job failed', [
            'restore_id' => $this->restoreId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Clean up working directory if it exists
        if (isset($this->workingDirectory) && is_dir($this->workingDirectory)) {
            FilesystemSupport::cleanupDirectory($this->workingDirectory);
        }

        // Mark the job as failed
        $restore = Restore::with('job')->findOrFail($this->restoreId);
        if ($restore->job->status !== 'failed') {
            $restore->job->markFailed($exception);
        }
    }
}

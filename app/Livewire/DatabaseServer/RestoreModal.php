<?php

namespace App\Livewire\DatabaseServer;

use App\Jobs\ProcessRestoreJob;
use App\Models\DatabaseServer;
use App\Models\Restore;
use App\Models\Snapshot;
use App\Services\Backup\DatabaseListService;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class RestoreModal extends Component
{
    use Toast;

    public ?DatabaseServer $targetServer = null;

    public ?string $selectedSourceServerId = null;

    public ?string $selectedSnapshotId = null;

    public string $schemaName = '';

    public int $currentStep = 1;

    public array $existingDatabases = [];

    public bool $showModal = false;

    public function mount(?string $targetServerId = null)
    {
        if ($targetServerId) {
            $this->targetServer = DatabaseServer::find($targetServerId);
        }
    }

    #[On('open-restore-modal')]
    public function openModal(string $targetServerId): void
    {
        $this->reset(['selectedSourceServerId', 'selectedSnapshotId', 'schemaName', 'currentStep', 'existingDatabases']);
        $this->targetServer = DatabaseServer::find($targetServerId);
        $this->currentStep = 1;

        $this->showModal = true;
    }

    public function selectSourceServer(string $serverId): void
    {
        $this->selectedSourceServerId = $serverId;
        $this->selectedSnapshotId = null;
        $this->currentStep = 2;
    }

    public function selectSnapshot(string $snapshotId): void
    {
        $this->selectedSnapshotId = $snapshotId;

        // Pre-fill schema name with original database name
        $snapshot = Snapshot::find($snapshotId);
        if ($snapshot) {
            $this->schemaName = $snapshot->database_name;
        }

        // Load existing databases for autocomplete
        $this->loadExistingDatabases();

        $this->currentStep = 3;
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function loadExistingDatabases(): void
    {
        if (! $this->targetServer) {
            return;
        }

        try {
            $databaseListService = app(DatabaseListService::class);
            $this->existingDatabases = $databaseListService->listDatabases($this->targetServer);
        } catch (\Exception $e) {
            $this->existingDatabases = [];
            // Silently fail - autocomplete just won't work
        }
    }

    public function restore(): void
    {
        $this->validate([
            'selectedSourceServerId' => 'required',
            'selectedSnapshotId' => 'required',
            'schemaName' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
        ], [
            'schemaName.required' => 'Please enter a database name.',
            'schemaName.regex' => 'Database name can only contain letters, numbers, and underscores.',
        ]);

        try {
            $snapshot = Snapshot::findOrFail($this->selectedSnapshotId);

            // Create a restore record to track the operation
            $restore = Restore::create([
                'snapshot_id' => $snapshot->id,
                'target_server_id' => $this->targetServer->id,
                'schema_name' => $this->schemaName,
                'status' => 'queued',
                'triggered_by_user_id' => auth()->id(),
            ]);

            // Dispatch the restore job
            ProcessRestoreJob::dispatch($restore->id);

            $this->success("Restore queued successfully! You'll be notified when it completes.");

            $this->showModal = false;

            $this->dispatch('restore-completed');
        } catch (\Exception $e) {
            $this->error('Failed to queue restore: '.$e->getMessage());
        }
    }

    public function getCompatibleServersProperty()
    {
        if (! $this->targetServer) {
            return collect();
        }

        return DatabaseServer::query()
            ->where('database_type', $this->targetServer->database_type)
            ->whereHas('snapshots', function ($query) {
                $query->where('status', 'completed');
            })
            ->with(['snapshots' => function ($query) {
                $query->where('status', 'completed')
                    ->orderBy('created_at', 'desc');
            }])
            ->get();
    }

    public function getSelectedSourceServerProperty()
    {
        if (! $this->selectedSourceServerId) {
            return null;
        }

        return DatabaseServer::with(['snapshots' => function ($query) {
            $query->where('status', 'completed')
                ->orderBy('created_at', 'desc');
        }])->find($this->selectedSourceServerId);
    }

    public function getSelectedSnapshotProperty()
    {
        if (! $this->selectedSnapshotId) {
            return null;
        }

        return Snapshot::find($this->selectedSnapshotId);
    }

    public function render()
    {
        return view('livewire.database-server.restore-modal');
    }
}

<?php

namespace App\Livewire\Menu;

use App\Models\BackupJob;
use Livewire\Component;

class JobsMenuItem extends Component
{
    public function getActiveJobsCountProperty(): int
    {
        return BackupJob::whereIn('status', ['running', 'pending'])->count();
    }

    public function render()
    {
        return <<<'HTML'
        <div wire:poll.5s>
            <x-menu-item
                title="{{ __('Jobs') }}"
                icon="o-queue-list"
                link="{{ route('jobs.index') }}"
                wire:navigate
                :badge="$this->activeJobsCount > 0 ? $this->activeJobsCount : null"
                badge-classes="badge-warning badge-soft"
            />
        </div>
        HTML;
    }
}

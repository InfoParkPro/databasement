<div>
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Snapshots') }}</flux:heading>
                <flux:subheading>{{ __('View and manage database backup snapshots') }}</flux:subheading>
            </div>
        </div>

        @if (session('status'))
            <x-alert variant="success" dismissible class="mb-6">
                {{ session('status') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert variant="danger" dismissible class="mb-6">
                {{ session('error') }}
            </x-alert>
        @endif

        <x-card :padding="false">
            <!-- Filters -->
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by server, database, host, or filename...') }}"
                            icon="magnifying-glass"
                            type="search"
                        />
                    </div>
                    <div class="sm:w-48">
                        <flux:select wire:model.live="statusFilter">
                            <option value="all">{{ __('All Statuses') }}</option>
                            <option value="completed">{{ __('Completed') }}</option>
                            <option value="failed">{{ __('Failed') }}</option>
                            <option value="running">{{ __('Running') }}</option>
                            <option value="pending">{{ __('Pending') }}</option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="table-default w-full">
                    <thead>
                        <tr>
                            <th class="table-th">
                                <button wire:click="sortBy('started_at')" class="group table-th-sortable">
                                    {{ __('Started') }}
                                    <span class="text-zinc-400">
                                        @if($sortField === 'started_at')
                                            @if($sortDirection === 'asc')
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        @else
                                            <svg class="h-4 w-4 opacity-0 group-hover:opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                            </svg>
                                        @endif
                                    </span>
                                </button>
                            </th>
                            <th class="table-th">{{ __('Server') }}</th>
                            <th class="table-th">{{ __('Database') }}</th>
                            <th class="table-th">{{ __('Status') }}</th>
                            <th class="table-th">{{ __('Duration') }}</th>
                            <th class="table-th">{{ __('Size') }}</th>
                            <th class="table-th">{{ __('Method') }}</th>
                            <th class="table-th-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $snapshot)
                            <tr>
                                <td>
                                    <div class="table-cell-primary">{{ $snapshot->started_at->format('M d, Y H:i') }}</div>
                                    <div>{{ $snapshot->started_at->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <div class="table-cell-primary">{{ $snapshot->databaseServer->name }}</div>
                                    <div>{{ $snapshot->database_host }}:{{ $snapshot->database_port }}</div>
                                </td>
                                <td>
                                    <div class="table-cell-primary">{{ $snapshot->database_name }}</div>
                                    <div><x-table-badge>{{ $snapshot->database_type }}</x-table-badge></div>
                                </td>
                                <td>
                                    @if($snapshot->status === 'completed')
                                        <x-table-badge variant="success">{{ __('Completed') }}</x-table-badge>
                                    @elseif($snapshot->status === 'failed')
                                        <x-table-badge variant="danger">{{ __('Failed') }}</x-table-badge>
                                    @elseif($snapshot->status === 'running')
                                        <x-table-badge variant="warning">{{ __('Running') }}</x-table-badge>
                                    @else
                                        <x-table-badge variant="info">{{ __('Pending') }}</x-table-badge>
                                    @endif
                                </td>
                                <td>
                                    @if($snapshot->getHumanDuration())
                                        {{ $snapshot->getHumanDuration() }}
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-cell-primary">{{ $snapshot->getHumanFileSize() }}</div>
                                    @if($snapshot->database_size_bytes)
                                        <div>DB: {{ $snapshot->getHumanDatabaseSize() }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-table-badge>{{ ucfirst($snapshot->method) }}</x-table-badge>
                                </td>
                                <td class="text-right">
                                    <div class="table-actions">
                                        @if($snapshot->status === 'completed')
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="arrow-down-tray"
                                                wire:click="download('{{ $snapshot->id }}')"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                {{ __('Download') }}
                                            </flux:button>
                                        @endif
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            wire:click="confirmDelete('{{ $snapshot->id }}')"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    @if($search || $statusFilter !== 'all')
                                        {{ __('No snapshots found matching your filters.') }}
                                    @else
                                        {{ __('No snapshots yet. Create a backup from the Database Servers page.') }}
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($snapshots->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $snapshots->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation-modal
        :title="__('Delete Snapshot')"
        :message="__('Are you sure you want to delete this snapshot? The backup file will be permanently removed.')"
        onConfirm="delete"
    />
</div>

<div>
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Database Servers') }}</flux:heading>
                <flux:subheading>{{ __('Manage your database server connections') }}</flux:subheading>
            </div>
            <flux:button variant="primary" :href="route('database-servers.create')" icon="plus" wire:navigate>
                {{ __('Add Server') }}
            </flux:button>
        </div>

        @if (session('status'))
            <x-alert variant="success" dismissible class="mb-6">
                {{ session('status') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert variant="error" dismissible class="mb-6">
                {{ session('error') }}
            </x-alert>
        @endif

        <x-card :padding="false">
            <!-- Search Bar -->
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by name, host, type, or description...') }}"
                    icon="magnifying-glass"
                    type="search"
                />
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="table-default w-full">
                    <thead>
                        <tr>
                            <th class="table-th">
                                <button wire:click="sortBy('name')" class="group table-th-sortable">
                                    {{ __('Name') }}
                                    <span class="text-zinc-400">
                                        @if($sortField === 'name')
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
                            <th class="table-th">
                                <button wire:click="sortBy('database_type')" class="group table-th-sortable">
                                    {{ __('Type') }}
                                    <span class="text-zinc-400">
                                        @if($sortField === 'database_type')
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
                            <th class="table-th">
                                <button wire:click="sortBy('host')" class="group table-th-sortable">
                                    {{ __('Host') }}
                                    <span class="text-zinc-400">
                                        @if($sortField === 'host')
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
                            <th class="table-th">
                                {{ __('Database') }}
                            </th>
                            <th class="table-th">
                                {{ __('Backup') }}
                            </th>
                            <th class="table-th">
                                <button wire:click="sortBy('created_at')" class="group table-th-sortable">
                                    {{ __('Created') }}
                                    <span class="text-zinc-400">
                                        @if($sortField === 'created_at')
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
                            <th class="table-th-right">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servers as $server)
                            <tr>
                                <td>
                                    <div class="table-cell-primary">{{ $server->name }}</div>
                                    @if($server->description)
                                        <div>{{ Str::limit($server->description, 50) }}</div>
                                    @endif
                                </td>
                                <td >
                                    <x-table-badge>{{ $server->database_type }}</x-table-badge>
                                </td>
                                <td>
                                    {{ $server->host }}:{{ $server->port }}
                                </td>
                                <td>
                                    {{ $server->database_name ?? '-' }}
                                </td>
                                <td>
                                    <div class="table-cell-primary">{{ $server->backup->volume->name }}</div>
                                    <div capitalize">{{ $server->backup->recurrence }}</div>
                                </td>
                                <td>
                                    {{ $server->created_at->diffForHumans() }}
                                </td>
                                <td class="text-right">
                                    <div class="table-actions">
                                        @if($server->backup)
                                            <flux:button size="sm" variant="ghost" icon="arrow-down-tray" wire:click="runBackup('{{ $server->id }}')" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ __('Backup Now') }}
                                            </flux:button>
                                        @endif
                                        <flux:button size="sm" variant="ghost" :href="route('database-servers.edit', $server)" icon="pencil" wire:navigate>
                                            {{ __('Edit') }}
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete('{{ $server->id }}')" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    @if($search)
                                        {{ __('No database servers found matching your search.') }}
                                    @else
                                        {{ __('No database servers yet.') }}
                                        <a href="{{ route('database-servers.create') }}" class="text-zinc-900 underline dark:text-zinc-100" wire:navigate>
                                            {{ __('Create your first one.') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($servers->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $servers->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation-modal
        :title="__('Delete Database Server')"
        :message="__('Are you sure you want to delete this database server? This action cannot be undone.')"
        onConfirm="delete"
    />
</div>

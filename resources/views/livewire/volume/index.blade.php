<div>
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Volumes') }}</flux:heading>
                <flux:subheading>{{ __('Manage your backup storage volumes') }}</flux:subheading>
            </div>
            <flux:button variant="primary" :href="route('volumes.create')" icon="plus" wire:navigate>
                {{ __('Add Volume') }}
            </flux:button>
        </div>

        @if (session('status'))
            <x-banner variant="success" dismissible class="mb-6">
                {{ session('status') }}
            </x-banner>
        @endif

        <x-card :padding="false">
            <!-- Search Bar -->
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by name or type...') }}"
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
                                <button wire:click="sortBy('type')" class="group table-th-sortable">
                                    {{ __('Type') }}
                                    <span class="text-zinc-400">
                                        @if($sortField === 'type')
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
                                {{ __('Configuration') }}
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
                        @forelse($volumes as $volume)
                            <tr>
                                <td>
                                    <div class="table-cell-primary">{{ $volume->name }}</div>
                                </td>
                                <td>
                                    <x-table-badge>{{ $volume->type }}</x-table-badge>
                                </td>
                                <td>
                                    @if($volume->type === 's3')
                                        <div>Bucket: {{ $volume->config['bucket'] }}</div>
                                        @if(!empty($volume->config['prefix']))
                                            <div>Prefix: {{ $volume->config['prefix'] }}</div>
                                        @endif
                                    @elseif($volume->type === 'local')
                                        <div>{{ $volume->config['path'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $volume->created_at->diffForHumans() }}
                                </td>
                                <td class="text-right">
                                    <div class="table-actions">
                                        <flux:button size="sm" variant="ghost" :href="route('volumes.edit', $volume)" icon="pencil" wire:navigate>
                                            {{ __('Edit') }}
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete('{{ $volume->id }}')" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">
                                    @if($search)
                                        {{ __('No volumes found matching your search.') }}
                                    @else
                                        {{ __('No volumes yet.') }}
                                        <a href="{{ route('volumes.create') }}" class="text-zinc-900 underline dark:text-zinc-100" wire:navigate>
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
            @if($volumes->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $volumes->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-delete-confirmation-modal
        :show="(bool) $deleteId"
        :title="__('Delete Volume')"
        :message="__('Are you sure you want to delete this volume? This action cannot be undone.')"
        onConfirm="delete"
        onCancel="cancelDelete"
    />
</div>

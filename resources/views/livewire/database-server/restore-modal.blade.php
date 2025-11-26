<flux:modal name="restore-modal" class="min-w-[40rem] max-w-3xl space-y-6">
    <div>
        <flux:heading size="lg">Restore Database Snapshot</flux:heading>
        <flux:subheading>
            Restore to: <strong>{{ $targetServer?->name }}</strong>
        </flux:subheading>

        <!-- Step Indicator -->
        <div class="mt-6 mb-8">
            <div class="flex items-center justify-center gap-3">
                <!-- Step 1 -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-4 py-2 rounded-full transition-all duration-300 {{ $currentStep === 1 ? 'bg-blue-600 text-white shadow-md' : ($currentStep > 1 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500') }}">
                        @if($currentStep > 1)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <span class="flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full {{ $currentStep === 1 ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-700' }}">
                        1
                    </span>
                        @endif
                        <span class="text-sm font-semibold">Select Source</span>
                    </div>

                    @if($currentStep >= 2)
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    @else
                        <div class="flex gap-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                        </div>
                    @endif
                </div>

                <!-- Step 2 -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-4 py-2 rounded-full transition-all duration-300 {{ $currentStep === 2 ? 'bg-blue-600 text-white shadow-md' : ($currentStep > 2 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500') }}">
                        @if($currentStep > 2)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            <span class="flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full {{ $currentStep === 2 ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-700' }}">
                        2
                    </span>
                        @endif
                        <span class="text-sm font-semibold">Select Snapshot</span>
                    </div>

                    @if($currentStep >= 3)
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    @else
                        <div class="flex gap-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                        </div>
                    @endif
                </div>

                <!-- Step 3 -->
                <div class="flex items-center gap-2 px-4 py-2 rounded-full transition-all duration-300 {{ $currentStep === 3 ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
            <span class="flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full {{ $currentStep === 3 ? 'bg-white/20' : 'bg-gray-200 dark:bg-gray-700' }}">
                3
            </span>
                    <span class="text-sm font-semibold">Destination</span>
                </div>
            </div>
        </div>

        <!-- Step 1: Select Source Server -->
        @if($currentStep === 1)
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Select a source database server to restore from. Only servers with the same database type ({{ $targetServer?->database_type }}) are shown.
                </p>

                @if($this->compatibleServers->isEmpty())
                    <div class="p-4 text-center border rounded-lg border-zinc-300 dark:border-zinc-700">
                        <p class="text-gray-600 dark:text-gray-400">No compatible database servers with snapshots found.</p>
                    </div>
                @else
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($this->compatibleServers as $server)
                            <div
                                wire:click="selectSourceServer('{{ $server->id }}')"
                                class="p-4 border rounded-lg cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 border-zinc-300 dark:border-zinc-700 {{ $selectedSourceServerId === $server->id ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : '' }}"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $server->name }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $server->host }}:{{ $server->port }}
                                            @if($server->database_name)
                                                • {{ $server->database_name }}
                                            @endif
                                        </div>
                                        @if($server->description)
                                            <div class="text-sm text-gray-500 dark:text-gray-500 mt-1">{{ $server->description }}</div>
                                        @endif
                                    </div>
                                    <div class="ml-4 text-sm text-gray-500 dark:text-gray-500">
                                        {{ $server->snapshots->count() }} {{ Str::plural('snapshot', $server->snapshots->count()) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Step 2: Select Snapshot -->
        @if($currentStep === 2)
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Select a snapshot to restore from <strong>{{ $this->selectedSourceServer?->name }}</strong>.
                </p>

                @if($this->selectedSourceServer?->snapshots->isEmpty())
                    <div class="p-4 text-center border rounded-lg border-zinc-300 dark:border-zinc-700">
                        <p class="text-gray-600 dark:text-gray-400">No completed snapshots found.</p>
                    </div>
                @else
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($this->selectedSourceServer->snapshots as $snapshot)
                            <div
                                wire:click="selectSnapshot('{{ $snapshot->id }}')"
                                class="p-4 border rounded-lg cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 border-zinc-300 dark:border-zinc-700 {{ $selectedSnapshotId === $snapshot->id ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : '' }}"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $snapshot->database_name }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            Created: {{ $snapshot->created_at->format('Y-m-d H:i:s') }} ({{ $snapshot->created_at->diffForHumans() }})
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                            Size: {{ $snapshot->getHumanFileSize() }}
                                            @if($snapshot->getDurationMs())
                                                • Duration: {{ $snapshot->getHumanDuration() }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-start mt-4">
                    <flux:button variant="ghost" wire:click="previousStep">
                        Back
                    </flux:button>
                </div>
            </div>
        @endif

        <!-- Step 3: Enter Destination Schema -->
        @if($currentStep === 3)
            <div class="space-y-4">
                <div>
                    <flux:label>Destination Database Name</flux:label>
                    <flux:input
                        wire:model.live="schemaName"
                        placeholder="Enter database name"
                        list="existing-databases"
                    />
                    @error('schemaName')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror

                    @if(!empty($existingDatabases))
                        <datalist id="existing-databases">
                            @foreach($existingDatabases as $db)
                                <option value="{{ $db }}">
                            @endforeach
                        </datalist>
                    @endif

                    <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                        ⚠️ If the database already exists, it will be deleted and replaced with the snapshot data.
                    </p>
                </div>

                @if($this->selectedSnapshot)
                    <div class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 border-zinc-300 dark:border-zinc-700">
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Restore Summary</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <div><strong>Source:</strong> {{ $this->selectedSourceServer?->name }} • {{ $this->selectedSnapshot->database_name }}</div>
                            <div><strong>Snapshot:</strong> {{ $this->selectedSnapshot->created_at->format('Y-m-d H:i:s') }}</div>
                            <div><strong>Target:</strong> {{ $targetServer?->name }} • {{ $schemaName ?: '(enter name)' }}</div>
                            <div><strong>Size:</strong> {{ $this->selectedSnapshot->getHumanFileSize() }}</div>
                        </div>
                    </div>
                @endif

                <div class="flex gap-2 mt-6">
                    <flux:button variant="ghost" wire:click="previousStep">
                        Back
                    </flux:button>
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="restore">
                        Restore Database
                    </flux:button>
                </div>
            </div>
        @endif

        <!-- Initial step buttons -->
        @if($currentStep === 1)
            <div class="flex gap-2 mt-6">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        @endif
    </div>
</flux:modal>

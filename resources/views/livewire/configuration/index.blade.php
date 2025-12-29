<div>
    <x-header title="{{ __('Configuration') }}" separator>
        <x-slot:subtitle>
            {{ __('Read-only view of the application configuration.') }}
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button
                label="{{ __('Documentation') }}"
                icon="o-book-open"
                link="https://david-crty.github.io/databasement/self-hosting/configuration"
                external
                class="btn-ghost"
            />
        </x-slot:actions>
    </x-header>

    <div class="grid gap-6">
        <!-- Backup Configuration -->
        <x-card title="{{ __('Backup') }}" subtitle="{{ __('Backup and restore operation settings.') }}" shadow>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-56">{{ __('Environment Variable') }}</th>
                            <th class="w-64">{{ __('Value') }}</th>
                            <th>{{ __('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backupConfig as $key => $config)
                            <tr>
                                <td class="font-mono text-sm">{{ $config['env'] }}</td>
                                <td class="font-mono text-sm text-base-content/80">{{ $config['value'] ?: '-' }}</td>
                                <td class="text-sm text-base-content/70">{{ $config['description'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Database Configuration -->
        <x-card title="{{ __('Database') }}" subtitle="{{ __('Application database connection settings.') }}" shadow>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('Environment Variable') }}</th>
                        <th class="w-64">{{ __('Value') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($databaseConfig as $key => $config)
                        <tr>
                            <td class="font-mono text-xs text-base-content/50">{{ $config['env'] }}</td>
                            <td class="font-mono text-sm text-base-content/80">{{ $config['value'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

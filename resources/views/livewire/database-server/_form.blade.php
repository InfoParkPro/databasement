@props(['form', 'submitLabel' => 'Save', 'cancelRoute' => 'database-servers.index', 'isEdit' => false])

<form wire:submit="save" class="space-y-6">
    <!-- Basic Information -->
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Basic Information') }}</flux:heading>

        <flux:input
            wire:model="form.name"
            :label="__('Server Name')"
            :placeholder="__('e.g., Production MySQL Server')"
            type="text"
            required
            autofocus
        />
        @error('form.name') <flux:error>{{ $message }}</flux:error> @enderror

        <flux:textarea
            wire:model="form.description"
            :label="__('Description')"
            :placeholder="__('Optional description for this server')"
            rows="3"
        />
        @error('form.description') <flux:error>{{ $message }}</flux:error> @enderror
    </div>

    <!-- Connection Details -->
    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Connection Details') }}</flux:heading>

        <flux:select
            wire:model="form.database_type"
            :label="__('Database Type')"
            required
        >
            <option value="mysql">MySQL</option>
            <option value="mariadb">MariaDB</option>
            <option value="postgresql">PostgreSQL</option>
            <option value="sqlite">SQLite</option>
        </flux:select>
        @error('form.database_type') <flux:error>{{ $message }}</flux:error> @enderror

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <flux:input
                    wire:model="form.host"
                    :label="__('Host')"
                    :placeholder="__('e.g., localhost or 192.168.1.100')"
                    type="text"
                    required
                />
                @error('form.host') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div>
                <flux:input
                    wire:model="form.port"
                    :label="__('Port')"
                    :placeholder="__('e.g., 3306')"
                    type="number"
                    required
                />
                @error('form.port') <flux:error>{{ $message }}</flux:error> @enderror
            </div>
        </div>

        <flux:input
            wire:model="form.database_name"
            :label="__('Database Name')"
            :placeholder="__('Optional: specific database name')"
            type="text"
        />
        @error('form.database_name') <flux:error>{{ $message }}</flux:error> @enderror
    </div>

    <!-- Authentication -->
    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Authentication') }}</flux:heading>

        <flux:input
            wire:model="form.username"
            :label="__('Username')"
            :placeholder="__('Database username')"
            type="text"
            required
            autocomplete="off"
        />
        @error('form.username') <flux:error>{{ $message }}</flux:error> @enderror

        <div>
            <flux:input
                wire:model="form.password"
                :label="__('Password')"
                :placeholder="$isEdit ? __('Leave blank to keep current password') : __('Database password')"
                type="password"
                :required="!$isEdit"
                autocomplete="off"
            />
            @if($isEdit)
                <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Only enter a new password if you want to change it.') }}
                </flux:text>
            @endif
            @error('form.password') <flux:error>{{ $message }}</flux:error> @enderror
        </div>

        <!-- Test Connection Button -->
        <div class="pt-2">
            <flux:button
                type="button"
                icon="arrow-path"
                variant="outline"
                wire:click="testConnection"
                :disabled="$form->testingConnection"
            >
                @if($form->testingConnection)
                    {{ __('Testing Connection...') }}
                @else
                    {{ __('Test Connection') }}
                @endif
            </flux:button>
        </div>

        <!-- Connection Test Result -->
        @if($form->connectionTestMessage)
            <div class="mt-2">
                @if($form->connectionTestSuccess)
                    <x-alert variant="success">
                        {{ $form->connectionTestMessage }}
                    </x-alert>
                @else
                    <x-alert variant="error">
                        {{ $form->connectionTestMessage }}
                    </x-alert>
                @endif
            </div>
        @endif
    </div>

    <!-- Backup Configuration -->
    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Backup Configuration') }}</flux:heading>

        <flux:select
            wire:model="form.volume_id"
            :label="__('Storage Volume')"
            required
        >
            <option value="">{{ __('Select a volume') }}</option>
            @foreach(\App\Models\Volume::orderBy('name')->get() as $volume)
                <option value="{{ $volume->id }}">{{ $volume->name }} ({{ $volume->type }})</option>
            @endforeach
        </flux:select>
        @error('form.volume_id') <flux:error>{{ $message }}</flux:error> @enderror

        <flux:select
            wire:model="form.recurrence"
            :label="__('Backup Frequency')"
            required
        >
            <option value="daily">{{ __('Daily') }}</option>
            <option value="weekly">{{ __('Weekly') }}</option>
        </flux:select>
        @error('form.recurrence') <flux:error>{{ $message }}</flux:error> @enderror
    </div>

    <!-- Submit Button -->
    <div class="flex items-center justify-end gap-3 pt-4">
        <flux:button variant="ghost" :href="route($cancelRoute)" wire:navigate>
            {{ __('Cancel') }}
        </flux:button>
        <flux:button variant="primary" type="submit">
            {{ __($submitLabel) }}
        </flux:button>
    </div>
</form>

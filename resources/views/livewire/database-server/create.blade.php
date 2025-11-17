<div>
    <div class="mx-auto max-w-4xl">
        <flux:heading size="xl" class="mb-2">{{ __('Create Database Server') }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Add a new database server to manage backups') }}</flux:subheading>

        @if (session('status'))
            <x-alert variant="success" dismissible class="mb-6">
                {{ session('status') }}
            </x-alert>
        @endif

        <x-card class="space-y-6">
            @include('livewire.database-server._form', [
                'form' => $form,
                'submitLabel' => 'Create Database Server',
                'isEdit' => false,
            ])
        </x-card>
    </div>
</div>

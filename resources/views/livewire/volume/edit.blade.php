<div>
    <div class="mx-auto max-w-4xl">
        <flux:heading size="xl" class="mb-2">{{ __('Edit Volume') }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Update storage volume configuration') }}</flux:subheading>

        @if (session('status'))
            <x-alert variant="success" dismissible class="mb-6">
                {{ session('status') }}
            </x-alert>
        @endif

        <x-card class="space-y-6">
            @include('livewire.volume._form', [
                'form' => $form,
                'submitLabel' => 'Update Volume',
            ])
        </x-card>
    </div>
</div>

@props(['title', 'message', 'onConfirm', 'onCancel'])

<flux:modal name="delete-confirmation" class="min-w-[22rem] space-y-6">
    <div>
        <flux:heading size="lg">{{ $title }}</flux:heading>

        <flux:subheading>
            <p>{{ $message }}</p>
        </flux:subheading>

        <div class="flex gap-2 mt-4">
            <flux:spacer />

            <flux:button variant="primary" wire:click="{{ $onConfirm }}" class="w-full sm:ml-3 sm:w-auto bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700">
                {{ __('Delete') }}
            </flux:button>
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

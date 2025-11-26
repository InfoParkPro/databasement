<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <x-header title="{{ __('Delete account') }}" subtitle="{{ __('Delete your account and all of its resources') }}" size="text-lg" />
    </div>

    <x-button
        label="{{ __('Delete account') }}"
        class="btn-error"
        @click="$wire.showDeleteModal = true"
        data-test="delete-user-button"
    />

    <x-modal wire:model="showDeleteModal" title="{{ __('Are you sure you want to delete your account?') }}" >
        <p class="mb-4">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <form wire:submit="deleteUser" class="space-y-6">
            <x-password wire:model="password" label="{{ __('Password') }}" />

            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" @click="$wire.showDeleteModal = false" />
                <x-button label="{{ __('Delete account') }}" class="btn-error" type="submit" data-test="confirm-delete-user-button" />
            </x-slot:actions>
        </form>
    </x-modal>
</section>

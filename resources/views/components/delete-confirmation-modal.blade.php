@props(['title', 'message', 'onConfirm', 'onCancel'])

<x-modal wire:model="showDeleteModal" title="{{ $title }}">
    <p>{{ $message }}</p>

    <x-slot:actions>
        <x-button label="{{ __('Cancel') }}" @click="$wire.showDeleteModal = false" />
        <x-button label="{{ __('Delete') }}" class="btn-error" wire:click="{{ $onConfirm }}" />
    </x-slot:actions>
</x-modal>

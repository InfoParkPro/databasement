<x-modal wire:model="showTokenModal" :title="__('Agent Token')" class="backdrop-blur" persistent box-class="max-w-2xl">

    <x-alert icon="o-exclamation-triangle" class="alert-warning mb-5">
        <div>
            <p class="font-semibold">{{ __('Save this token — it will not be shown again.') }}</p>
            <p class="text-sm opacity-80 mt-0.5">
                {{ __('Store it somewhere safe before closing this dialog.') }}
            </p>
        </div>
    </x-alert>

    <x-tabs selected="docker-tab" label-class="tabs-sm">



        {{-- Docker tab --}}
        <x-tab name="docker-tab" :label="__('Docker')" icon="devicon.docker">
            <div class="flex items-center justify-between mb-1.5">
                <p class="text-xs font-semibold uppercase tracking-widest opacity-50">
                    {{ __('Run the agent as a Docker container') }}
                </p>
                <x-button
                    icon="o-clipboard-document"
                    class="btn-ghost btn-xs"
                    :label="__('Copy')"
                    x-clipboard="$wire.dockerCommand"
                    x-on:clipboard-copied="$wire.success('{{ __('Copied to clipboard!') }}', null, 'toast-bottom')"
                />
            </div>
            <pre class="bg-neutral text-neutral-content rounded-box p-5 text-sm overflow-x-auto"><code class="break-all select-all whitespace-pre-wrap">{{ $dockerCommand }}</code></pre>
        </x-tab>

        {{-- Environment Variables tab --}}
        <x-tab name="env-tab" :label="__('Environment Variables')" icon="o-command-line">
            <div class="flex items-center justify-between mb-1.5">
                <p class="text-xs font-semibold uppercase tracking-widest opacity-50">
                    {{ __('Set these in your agent environment') }}
                </p>
                <x-button
                    icon="o-clipboard-document"
                    class="btn-ghost btn-xs"
                    :label="__('Copy')"
                    x-clipboard="$wire.envVars"
                    x-on:clipboard-copied="$wire.success('{{ __('Copied to clipboard!') }}', null, 'toast-bottom')"
                />
            </div>
            <pre class="bg-neutral text-neutral-content rounded-box p-5 text-sm overflow-x-auto"><code class="break-all select-all whitespace-pre-wrap">{{ $envVars }}</code></pre>
        </x-tab>

        {{-- Token tab --}}
        <x-tab name="token-tab" :label="__('Token')" icon="o-key">
            <div class="flex items-center justify-between mb-1.5">
                <p class="text-xs font-semibold uppercase tracking-widest opacity-50">
                    {{ __('Agent Token') }}
                </p>
                <x-button
                    icon="o-clipboard-document"
                    class="btn-ghost btn-xs"
                    :label="__('Copy')"
                    x-clipboard="$wire.newToken"
                    x-on:clipboard-copied="$wire.success('{{ __('Copied to clipboard!') }}', null, 'toast-bottom')"
                />
            </div>
            <pre class="bg-neutral text-neutral-content rounded-box p-5 text-sm overflow-x-auto"><code class="break-all select-all whitespace-pre-wrap">{{ $newToken }}</code></pre>
        </x-tab>

    </x-tabs>

    <x-slot:actions>
        <x-button :label="__('Done')" class="btn-primary" wire:click="closeTokenModal" />
    </x-slot:actions>

</x-modal>

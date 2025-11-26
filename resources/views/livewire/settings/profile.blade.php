<div>
    <div class="mx-auto max-w-4xl">
        <x-header title="{{ __('Profile') }}" subtitle="{{ __('Update your name and email address') }}" size="text-2xl" separator class="mb-6" />

        @if (session('success'))
            <x-alert class="alert-success mb-6" icon="o-check-circle" dismissible>
                {{ session('success') }}
            </x-alert>
        @endif

        <x-card>
            <form wire:submit="updateProfileInformation" class="space-y-6">
                <x-input wire:model="name" label="{{ __('Name') }}" type="text" required autofocus autocomplete="name" />

                <div>
                    <x-input wire:model="email" label="{{ __('Email') }}" type="email" required autocomplete="email" />

                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                        <div>
                            <p class="mt-4 text-sm">
                                {{ __('Your email address is unverified.') }}

                                <a href="#" class="link link-primary text-sm" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </a>
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-2 font-medium text-success text-sm">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end">
                    <x-button type="submit" class="btn-primary" label="{{ __('Save') }}" data-test="update-profile-button" />
                </div>
            </form>
        </x-card>

        <livewire:settings.delete-user-form />
    </div>
</div>

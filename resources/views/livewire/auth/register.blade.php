<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <div class="flex w-full flex-col text-center">
            <h1 class="text-2xl font-bold">{{ __('Create an account') }}</h1>
            <p class="text-sm opacity-70">{{ __('Enter your details below to create your account') }}</p>
        </div>

        @if (session('status'))
            <x-alert class="alert-success" icon="o-check-circle">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <x-input
                name="name"
                label="{{ __('Name') }}"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Full name') }}"
            />

            <!-- Email Address -->
            <x-input
                name="email"
                label="{{ __('Email address') }}"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <x-password
                name="password"
                label="{{ __('Password') }}"
                required
                autocomplete="new-password"
                placeholder="{{ __('Password') }}"
            />

            <!-- Confirm Password -->
            <x-password
                name="password_confirmation"
                label="{{ __('Confirm password') }}"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm password') }}"
            />

            <div class="flex items-center justify-end">
                <x-button type="submit" class="btn-primary w-full" label="{{ __('Create account') }}" data-test="register-user-button" />
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" class="link link-primary" wire:navigate>{{ __('Log in') }}</a>
        </div>
    </div>
</x-layouts.auth>

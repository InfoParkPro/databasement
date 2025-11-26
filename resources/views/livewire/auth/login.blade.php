<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <div class="flex w-full flex-col text-center">
            <h1 class="text-2xl font-bold">{{ __('Log in to your account') }}</h1>
            <p class="text-sm opacity-70">{{ __('Enter your email and password below to log in') }}</p>
        </div>

        @if (session('status'))
            <x-alert class="alert-success" icon="o-check-circle">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                label="{{ __('Email address') }}"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <x-password
                    name="password"
                    label="{{ __('Password') }}"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                />

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="absolute top-0 text-sm end-0 link link-hover" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <!-- Remember Me -->
            <x-checkbox name="remember" label="{{ __('Remember me') }}" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <x-button type="submit" class="btn-primary w-full" label="{{ __('Log in') }}" data-test="login-button" />
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse">
                <span>{{ __('Don\'t have an account?') }}</span>
                <a href="{{ route('register') }}" class="link link-primary" wire:navigate>{{ __('Sign up') }}</a>
            </div>
        @endif
    </div>
</x-layouts.auth>

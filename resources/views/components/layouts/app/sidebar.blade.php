<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen font-sans antialiased bg-base-200/50">

        {{-- NAVBAR mobile only --}}
        <x-nav sticky class="lg:hidden">
            <x-slot:brand>
                <x-app-logo />
            </x-slot:brand>
            <x-slot:actions>
                <label for="main-drawer" class="lg:hidden me-3">
                    <x-icon name="o-bars-3" class="cursor-pointer" />
                </label>
            </x-slot:actions>
        </x-nav>

        {{-- MAIN --}}
        <x-main full-width>
            {{-- SIDEBAR --}}
            <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

                {{-- BRAND --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 pt-4" wire:navigate>
                    <x-app-logo />
                </a>

                {{-- MENU --}}
                <x-menu activate-by-route>

                    {{-- Platform --}}
                    <x-menu-separator title="{{ __('Platform') }}" />

                    <x-menu-item title="{{ __('Dashboard') }}" icon="o-home" link="{{ route('dashboard') }}" wire:navigate />
                    <x-menu-item title="{{ __('Database Servers') }}" icon="o-server-stack" link="{{ route('database-servers.index') }}" wire:navigate />
                    <x-menu-item title="{{ __('Snapshots') }}" icon="o-camera" link="{{ route('snapshots.index') }}" wire:navigate />
                    <x-menu-item title="{{ __('Volumes') }}" icon="o-circle-stack" link="{{ route('volumes.index') }}" wire:navigate />

                    {{-- Settings --}}
                    <x-menu-separator title="{{ __('Settings') }}" />

                    <x-menu-item title="{{ __('Profile') }}" icon="o-user" link="{{ route('profile.edit') }}" wire:navigate />
                    <x-menu-item title="{{ __('Password') }}" icon="o-key" link="{{ route('user-password.edit') }}" wire:navigate />
                    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                        <x-menu-item title="{{ __('Two-Factor Auth') }}" icon="o-shield-check" link="{{ route('two-factor.show') }}" wire:navigate />
                    @endif
                    <x-menu-item title="{{ __('Appearance') }}" icon="o-paint-brush" link="{{ route('appearance.edit') }}" wire:navigate />

                </x-menu>

                {{-- USER --}}
                <x-slot:actions>
                    <x-dropdown>
                        <x-slot:trigger>
                            <x-button icon="o-user" class="btn-circle btn-ghost" />
                        </x-slot:trigger>

                        <x-menu-item title="{{ auth()->user()->name }}" class="font-semibold" />
                        <x-menu-item title="{{ auth()->user()->email }}" class="text-xs opacity-60" />
                        <x-menu-separator />
                        <x-menu-item title="{{ __('Settings') }}" icon="o-cog-6-tooth" link="{{ route('profile.edit') }}" no-wire-navigate />
                        <x-menu-separator />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-menu-item title="{{ __('Log Out') }}" icon="o-arrow-right-start-on-rectangle" onclick="this.closest('form').submit()" data-test="logout-button" />
                        </form>
                    </x-dropdown>
                </x-slot:actions>
            </x-slot:sidebar>

            {{-- The $slot content --}}
            <x-slot:content>
                {{ $slot }}
            </x-slot:content>
        </x-main>

        {{--  TOAST area --}}
        <x-toaster-hub />
    </body>
</html>

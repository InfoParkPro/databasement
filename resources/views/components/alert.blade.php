@props([
    'variant' => 'info', // success, error, warning, info
    'dismissible' => false,
])

@php
    // Map variants to Tailwind classes
    $variantClasses = [
        'success' => [
            'container' => 'bg-green-500/10 outline-green-500/20',
            'icon' => 'text-green-400',
            'text' => 'text-green-300',
            'button' => 'text-green-400 hover:bg-green-500/10 focus-visible:ring-green-500 focus-visible:ring-offset-green-900',
        ],
        'error' => [
            'container' => 'bg-red-500/10 outline-red-500/20',
            'icon' => 'text-red-400',
            'text' => 'text-red-300',
            'button' => 'text-red-400 hover:bg-red-500/10 focus-visible:ring-red-500 focus-visible:ring-offset-red-900',
        ],
        'warning' => [
            'container' => 'bg-amber-500/10 outline-amber-500/20',
            'icon' => 'text-amber-400',
            'text' => 'text-amber-300',
            'button' => 'text-amber-400 hover:bg-amber-500/10 focus-visible:ring-amber-500 focus-visible:ring-offset-amber-900',
        ],
        'info' => [
            'container' => 'bg-blue-500/10 outline-blue-500/20',
            'icon' => 'text-blue-400',
            'text' => 'text-blue-300',
            'button' => 'text-blue-400 hover:bg-blue-500/10 focus-visible:ring-blue-500 focus-visible:ring-offset-blue-900',
        ],
    ];

    $classes = $variantClasses[$variant] ?? $variantClasses['info'];
@endphp

<div
    @if($dismissible)
        x-data="{ show: true }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
    @endif
    {{ $attributes->merge(['class' => "rounded-md p-4 outline {$classes['container']}"]) }}
>
    <div class="flex">
        <div class="shrink-0">
            <div class="size-5 {{ $classes['icon'] }}">
                @if($variant === 'success')
                    <x-fas-circle-check />
                @elseif($variant === 'error')
                    <x-fas-circle-xmark />
                @elseif($variant === 'warning')
                    <x-fas-triangle-exclamation />
                @else
                    <x-fas-circle-info />
                @endif
            </div>
        </div>

        <div class="ml-3">
            <div class="text-sm font-medium {{ $classes['text'] }}">
                {{ $slot }}
            </div>
        </div>

        @if($dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        type="button"
                        @click="show = false"
                        class="inline-flex rounded-md p-1.5 focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:outline-hidden {{ $classes['button'] }}"
                    >
                        <span class="sr-only">Dismiss</span>
                        <div class="size-3">
                            <x-fas-xmark />
                        </div>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

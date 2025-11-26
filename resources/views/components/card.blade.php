@props([
    'padding' => true,
])

@php
    $baseClasses = 'card bg-base-100 border border-base-300';
    $paddingClass = $padding ? 'p-6' : '';
@endphp

<div {{ $attributes->merge(['class' => "{$baseClasses} {$paddingClass}"]) }}>
    {{ $slot }}
</div>

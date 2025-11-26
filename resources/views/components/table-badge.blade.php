@props(['variant' => 'default'])

@php
$classes = 'badge';
$variantClasses = match($variant) {
    'success' => 'badge-success',
    'error', 'danger' => 'badge-error',
    'warning' => 'badge-warning',
    'info' => 'badge-info',
    default => 'badge-neutral',
};
@endphp

<span {{ $attributes->merge(['class' => $classes . ' ' . $variantClasses]) }}>
    {{ $slot }}
</span>

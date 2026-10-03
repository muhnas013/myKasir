@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'badge badge--neutral',
        'success' => 'badge badge--success',
        'warning' => 'badge badge--warning',
        'danger' => 'badge badge--danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => $variants[$variant] ?? $variants['neutral']]) }}>
    {{ $slot }}
</span>

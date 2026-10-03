@props(['variant' => 'primary', 'type' => 'button'])

@php
    $variants = [
        'primary' => 'btn btn--primary',
        'secondary' => 'btn btn--secondary',
        'ghost' => 'btn btn--ghost',
        'danger' => 'btn btn--danger',
    ];
    $class = $variants[$variant] ?? $variants['primary'];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot }}
</button>

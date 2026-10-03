@props(['label' => null, 'name' => null, 'error' => null, 'options' => []])

<label class="field">
    @if ($label)
        <span class="field__label">{{ $label }}</span>
    @endif
    <select
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'input'.($error ? ' input--error' : '')]) }}
    >
        {{ $slot }}
    </select>
    @if ($error)
        <span class="field-error">{{ $error }}</span>
    @endif
</label>

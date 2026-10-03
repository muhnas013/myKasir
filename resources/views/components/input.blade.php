@props(['label' => null, 'name' => null, 'error' => null, 'type' => 'text'])

<label class="field">
    @if ($label)
        <span class="field__label">{{ $label }}</span>
    @endif
    <input
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'input'.($error ? ' input--error' : '')]) }}
    />
    @if ($error)
        <span class="field-error">{{ $error }}</span>
    @endif
</label>

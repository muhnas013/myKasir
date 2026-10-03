@props(['label' => null])

<label class="toggle-row">
    @if ($label)
        <span class="field__label">{{ $label }}</span>
    @endif
    <span class="toggle">
        <input type="checkbox" role="switch" {{ $attributes->merge(['class' => 'toggle__input']) }} />
        <span class="toggle__track"><span class="toggle__dot"></span></span>
    </span>
</label>

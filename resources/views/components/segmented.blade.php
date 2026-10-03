@props(['options', 'current', 'model'])

<div class="segmented" role="group">
    @foreach ($options as $value => $label)
        <button type="button" class="segmented__item @if($current === $value) is-active @endif" aria-pressed="{{ $current === $value ? 'true' : 'false' }}" wire:click="$set('{{ $model }}', '{{ $value }}')">
            {{ $label }}
        </button>
    @endforeach
</div>

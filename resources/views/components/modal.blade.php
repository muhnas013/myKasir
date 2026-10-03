@props(['title', 'close' => 'closeModal'])

<div class="modal" x-data x-on:keydown.escape.window="$wire.{{ $close }}()">
    <div class="modal__backdrop" wire:click="{{ $close }}"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="{{ $title }}" x-trap.noscroll>
        <div class="card__title">{{ $title }}</div>
        {{ $slot }}
    </div>
</div>

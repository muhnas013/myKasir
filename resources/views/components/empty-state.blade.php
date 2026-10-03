@props(['title', 'icon' => 'inbox'])

<div class="empty-state">
    <x-icon :name="$icon" class="empty-state__icon" />
    <p class="empty-state__title">{{ $title }}</p>
    @if (isset($slot) && trim($slot))
        <div class="empty-state__action">{{ $slot }}</div>
    @endif
</div>

<div>
    <div class="app-topbar">
        <h1>Pengaturan</h1>
    </div>

    <div class="tabs">
        <button type="button" class="tabs__item @if($tab === 'general') is-active @endif" wire:click="$set('tab', 'general')">
            Outlet &amp; Pembayaran
        </button>
        <button type="button" class="tabs__item @if($tab === 'users') is-active @endif" wire:click="$set('tab', 'users')">
            Pengguna
        </button>
    </div>

    @if ($tab === 'general')
        @livewire('settings.general')
    @else
        @livewire('settings.users')
    @endif
</div>

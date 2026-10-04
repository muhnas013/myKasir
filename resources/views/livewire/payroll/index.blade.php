<div>
    <div class="app-topbar">
        <h1>Penggajian</h1>
    </div>

    <div class="tabs">
        <button type="button" class="tabs__item @if($tab === 'summary') is-active @endif" wire:click="$set('tab', 'summary')">
            Rekap Upah
        </button>
        <button type="button" class="tabs__item @if($tab === 'activities') is-active @endif" wire:click="$set('tab', 'activities')">
            Kelola Aktivitas Bonus
        </button>
    </div>

    @if ($tab === 'summary')
        @livewire('payroll.wage-summary')
    @else
        @livewire('payroll.activity-table')
    @endif
</div>

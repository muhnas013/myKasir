<div>
    <div class="app-topbar">
        <h1>Stok</h1>
    </div>

    <div class="tabs">
        <button type="button" class="tabs__item @if($tab === 'bahan') is-active @endif" wire:click="$set('tab', 'bahan')">
            Bahan Baku
        </button>
        <button type="button" class="tabs__item @if($tab === 'resep') is-active @endif" wire:click="$set('tab', 'resep')">
            Resep
        </button>
    </div>

    @if ($tab === 'bahan')
        @livewire('stock.ingredient-table')
    @else
        @livewire('stock.recipe-editor')
    @endif
</div>

<div>
    <div class="app-topbar">
        <h1>Menu</h1>
    </div>

    <div class="tabs">
        <button type="button" class="tabs__item @if($tab === 'products') is-active @endif" wire:click="$set('tab', 'products')">
            Produk
        </button>
        <button type="button" class="tabs__item @if($tab === 'categories') is-active @endif" wire:click="$set('tab', 'categories')">
            Kategori
        </button>
    </div>

    @if ($tab === 'products')
        @livewire('menu.product-table')
    @else
        @livewire('menu.categories')
    @endif
</div>

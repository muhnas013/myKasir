<div
    x-data="{
        gridSize: localStorage.getItem('mykasir_menu_grid_size') || 'md',
        view: localStorage.getItem('mykasir_menu_view') || 'grid',
        setGridSize(v) { this.gridSize = v; localStorage.setItem('mykasir_menu_grid_size', v); },
        setView(v) { this.view = v; localStorage.setItem('mykasir_menu_view', v); },
    }"
>
    <div class="tabs">
        <button type="button" class="tabs__item @if($categoryFilter === '') is-active @endif" wire:click="$set('categoryFilter', '')">Semua</button>
        @foreach ($categories as $category)
            <button type="button" class="tabs__item @if($categoryFilter === (string) $category->id) is-active @endif" wire:click="$set('categoryFilter', '{{ $category->id }}')">
                {{ $category->name }}
            </button>
        @endforeach
    </div>

    <div class="view-toggle">
        <div class="view-toggle__sizes" x-show="view === 'grid'" x-cloak>
            <button type="button" class="view-toggle__btn" :class="{ 'is-active': gridSize === 'sm' }" @click="setGridSize('sm')" aria-label="Grid kecil">K</button>
            <button type="button" class="view-toggle__btn" :class="{ 'is-active': gridSize === 'md' }" @click="setGridSize('md')" aria-label="Grid sedang">S</button>
            <button type="button" class="view-toggle__btn" :class="{ 'is-active': gridSize === 'lg' }" @click="setGridSize('lg')" aria-label="Grid besar">B</button>
        </div>
        <div class="view-toggle__modes">
            <button type="button" class="view-toggle__btn" :class="{ 'is-active': view === 'grid' }" @click="setView('grid')" aria-label="Tampilan grid"><x-icon name="layout-grid" /></button>
            <button type="button" class="view-toggle__btn" :class="{ 'is-active': view === 'list' }" @click="setView('list')" aria-label="Tampilan daftar"><x-icon name="list" /></button>
        </div>
    </div>

    <div wire:loading.delay wire:target="categoryFilter" class="skeleton skeleton--block"></div>

    <div wire:loading.remove wire:target="categoryFilter">
        @if ($products->isEmpty())
            <x-empty-state title="Belum ada menu aktif." icon="inbox">
                @can('menu.manage')
                    <a href="{{ route('menu.index') }}" class="btn btn--primary">Tambah Menu</a>
                @endcan
            </x-empty-state>
        @else
            <div class="menu-grid" :class="view === 'list' ? 'menu-grid--list' : 'menu-grid--' + gridSize">
                @foreach ($products as $product)
                    @php($soldOut = ! $stock->isSellable($product))
                    <button type="button" class="menu-tile" wire:key="tile-{{ $product->id }}" wire:click="selectProduct({{ $product->id }})" @disabled($soldOut)>
                        <div class="menu-tile__photo menu-tile__photo--cat-{{ ($product->category_id - 1) % 4 + 1 }}">
                            @if ($product->photo_url)
                                <img src="{{ $product->photo_url }}" alt="" loading="lazy">
                            @else
                                <x-icon name="utensils" />
                            @endif
                            @if ($soldOut)
                                <x-badge variant="danger" class="menu-tile__badge">Habis</x-badge>
                            @endif
                        </div>
                        <div class="menu-tile__info">
                            <span class="menu-tile__name">{{ $product->name }}</span>
                            <span class="menu-tile__price">{{ \App\Support\Money::format($product->price) }}</span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($selecting)
        <x-modal :title="$selecting->name">
            <form wire:submit="confirmOptions">
                @foreach ($selecting->variantGroups as $group)
                    <fieldset class="option-group" wire:key="vg-{{ $group->id }}">
                        <legend class="field__label">
                            {{ $group->name }}
                            @if ($group->is_required) <x-badge variant="warning">Wajib</x-badge> @endif
                        </legend>
                        <div class="option-list">
                            @foreach ($group->options as $option)
                                <label class="option-item" wire:key="vo-{{ $option->id }}">
                                    @if ($group->max_select === 1)
                                        <input type="radio" name="group-{{ $group->id }}" value="{{ $option->id }}" wire:model="selected.{{ $group->id }}">
                                    @else
                                        <input type="checkbox" value="{{ $option->id }}" wire:model="selected.{{ $group->id }}">
                                    @endif
                                    <span>{{ $option->name }}</span>
                                    @if ($option->price_delta > 0)
                                        <span class="option-item__price">+ {{ \App\Support\Money::format($option->price_delta) }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('selected.'.$group->id)
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </fieldset>
                @endforeach
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Tambah</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeModal">Batal</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>

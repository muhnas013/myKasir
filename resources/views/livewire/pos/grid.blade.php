<div>
    <div class="tabs">
        <button type="button" class="tabs__item @if($categoryFilter === '') is-active @endif" wire:click="$set('categoryFilter', '')">Semua</button>
        @foreach ($categories as $category)
            <button type="button" class="tabs__item @if($categoryFilter === (string) $category->id) is-active @endif" wire:click="$set('categoryFilter', '{{ $category->id }}')">
                {{ $category->name }}
            </button>
        @endforeach
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
            <div class="menu-grid">
                @foreach ($products as $product)
                    <button type="button" class="menu-tile menu-tile--cat-{{ ($product->category_id - 1) % 4 + 1 }}" wire:key="tile-{{ $product->id }}" wire:click="selectProduct({{ $product->id }})">
                        <span class="menu-tile__name">{{ $product->name }}</span>
                        <span class="menu-tile__price">{{ \App\Support\Money::format($product->price) }}</span>
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

<div>
    <div class="card">
        <div class="card__title">Resep</div>

        <x-select label="Pilih produk" wire:model="productId" wire:change="selectProduct">
            <option value="">— pilih produk —</option>
            @foreach ($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </x-select>
    </div>

    <div wire:loading.delay wire:target="selectProduct" class="skeleton skeleton--block"></div>

    @if (! $product)
        <x-empty-state title="Pilih produk untuk mengatur resepnya." icon="inbox" />
    @else
        <form wire:submit="save" wire:loading.remove wire:target="selectProduct" class="field-group">
            <div class="card">
                <div class="card__title">Resep dasar — {{ $product->name }} (per 1 porsi)</div>

                @if (empty($baseLines))
                    <x-empty-state title="Belum ada resep dasar. Tambah bahan." icon="inbox" />
                @endif

                @foreach ($baseLines as $index => $line)
                    <div class="card__row" wire:key="base-{{ $index }}">
                        <x-select wire:model="baseLines.{{ $index }}.ingredient_id" :error="$errors->first('baseLines.'.$index.'.ingredient_id')">
                            <option value="">— bahan —</option>
                            @foreach ($ingredients as $ingredient)
                                <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                            @endforeach
                        </x-select>
                        <x-input type="number" step="0.001" min="0" placeholder="Qty" wire:model="baseLines.{{ $index }}.qty" :error="$errors->first('baseLines.'.$index.'.qty')" />
                        <x-button type="button" variant="ghost" wire:click="removeBaseLine({{ $index }})" aria-label="Hapus baris">
                            <x-icon name="x" />
                        </x-button>
                    </div>
                @endforeach

                <x-button type="button" variant="secondary" wire:click="addBaseLine"><x-icon name="plus" /> Tambah Bahan</x-button>
            </div>

            @foreach ($product->variantGroups as $group)
                <div class="card">
                    <div class="card__title">Resep opsi — {{ $group->name }}</div>

                    @foreach ($group->options as $option)
                        <div class="card__row">
                            <strong>{{ $option->name }}</strong>
                        </div>

                        @foreach (($optionLines[$option->id] ?? []) as $index => $line)
                            <div class="card__row" wire:key="opt-{{ $option->id }}-{{ $index }}">
                                <x-select wire:model="optionLines.{{ $option->id }}.{{ $index }}.ingredient_id" :error="$errors->first('optionLines.'.$option->id.'.'.$index.'.ingredient_id')">
                                    <option value="">— bahan —</option>
                                    @foreach ($ingredients as $ingredient)
                                        <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                    @endforeach
                                </x-select>
                                <x-input type="number" step="0.001" min="0" placeholder="Qty tambahan" wire:model="optionLines.{{ $option->id }}.{{ $index }}.qty" :error="$errors->first('optionLines.'.$option->id.'.'.$index.'.qty')" />
                                <x-button type="button" variant="ghost" wire:click="removeOptionLine({{ $option->id }}, {{ $index }})" aria-label="Hapus baris">
                                    <x-icon name="x" />
                                </x-button>
                            </div>
                        @endforeach

                        <x-button type="button" variant="secondary" wire:click="addOptionLine({{ $option->id }})"><x-icon name="plus" /> Tambah Bahan untuk {{ $option->name }}</x-button>
                    @endforeach
                </div>
            @endforeach

            <x-button type="submit" wire:loading.attr="disabled">Simpan Resep</x-button>
        </form>
    @endif
</div>

<div>
    @if ($lowStock->isNotEmpty())
        <div class="stock-alert">
            <x-icon name="triangle-alert" />
            <div>
                <div class="stock-alert__title">Hampir habis</div>
                <div class="stock-alert__list">
                    @foreach ($lowStock as $ingredient)
                        <x-badge variant="warning">{{ $ingredient->name }} ({{ rtrim(rtrim($ingredient->stock_qty, '0'), '.') ?: '0' }} {{ $ingredient->unit }})</x-badge>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card__header">
            <div class="card__title">Bahan Baku</div>
            <x-button wire:click="create" wire:loading.attr="disabled"><x-icon name="plus" /> Tambah Bahan</x-button>
        </div>

        <x-input placeholder="Cari bahan..." wire:model.live.debounce.400ms="search" aria-label="Cari bahan" />

        <div wire:loading.delay class="skeleton skeleton--block"></div>

        <div wire:loading.remove>
            @if ($ingredients->isEmpty())
                <x-empty-state title="Belum ada bahan baku. Tambah bahan pertama." icon="inbox" />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Satuan</th>
                            <th>Stok</th>
                            <th>Min.</th>
                            <th>Avg Cost /1000</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ingredients as $ingredient)
                            <tr wire:key="ingredient-{{ $ingredient->id }}">
                                <td>{{ $ingredient->name }}</td>
                                <td>{{ $ingredient->unit }}</td>
                                <td>
                                    {{ rtrim(rtrim($ingredient->stock_qty, '0'), '.') ?: '0' }}
                                    @if ($ingredient->stock_qty <= $ingredient->min_qty)
                                        <x-badge variant="warning">Hampir habis</x-badge>
                                    @endif
                                </td>
                                <td>{{ rtrim(rtrim($ingredient->min_qty, '0'), '.') ?: '0' }}</td>
                                <td>Rp {{ number_format($ingredient->avg_cost, 0, ',', '.') }}</td>
                                <td class="table__actions">
                                    <x-button variant="secondary" wire:click="openPurchase({{ $ingredient->id }})">Stok Masuk</x-button>
                                    <x-button variant="secondary" wire:click="openOpname({{ $ingredient->id }})">Opname</x-button>
                                    <x-button variant="secondary" wire:click="edit({{ $ingredient->id }})" aria-label="Ubah {{ $ingredient->name }}"><x-icon name="pencil" /></x-button>
                                    <x-button variant="ghost" wire:click="delete({{ $ingredient->id }})" wire:confirm="Hapus {{ $ingredient->name }}?">Hapus</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </div>
    </div>

    @if ($showForm)
        <div class="card">
            <div class="card__title">{{ $editingId ? 'Ubah Bahan' : 'Tambah Bahan' }}</div>
            <form wire:submit="save" class="field-group">
                <div class="card__row">
                    <x-input label="Nama" wire:model="name" :error="$errors->first('name')" />
                    <x-select label="Satuan" wire:model="unit" :error="$errors->first('unit')">
                        <option value="g">g (gram)</option>
                        <option value="ml">ml (mililiter)</option>
                        <option value="pcs">pcs</option>
                    </x-select>
                    <x-input label="Batas peringatan (min.)" type="number" step="0.001" min="0" wire:model="min_qty" :error="$errors->first('min_qty')" />
                </div>
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Simpan</x-button>
                    <x-button type="button" variant="secondary" wire:click="cancel">Batal</x-button>
                </div>
            </form>
        </div>
    @endif

    @if ($purchaseId)
        <x-modal title="Stok Masuk" close="closePurchase">
            <form wire:submit="receivePurchase" class="field-group">
                <x-input label="Jumlah masuk" type="number" step="0.001" min="0" wire:model="purchase_qty" :error="$errors->first('purchase_qty')" />
                <x-input label="Harga beli total (Rp)" type="number" min="0" wire:model="purchase_total_cost" :error="$errors->first('purchase_total_cost')" />
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Simpan</x-button>
                    <x-button type="button" variant="secondary" wire:click="closePurchase">Batal</x-button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($opnameId)
        <x-modal title="Opname Stok" close="closeOpname">
            <form wire:submit="saveOpname" class="field-group">
                <x-input label="Stok fisik sebenarnya" type="number" step="0.001" min="0" wire:model="opname_qty" :error="$errors->first('opname_qty')" />
                <x-input label="Alasan" wire:model="opname_reason" :error="$errors->first('opname_reason')" />
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Simpan</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeOpname">Batal</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>

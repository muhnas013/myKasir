<div>
    <div class="card">
        <div class="card__header">
            <div class="card__title">Produk</div>
            <x-button wire:click="create" wire:loading.attr="disabled"><x-icon name="plus" /> Tambah Produk</x-button>
        </div>

        <div class="card__row filter-row">
            <x-input label="Cari" placeholder="Nama atau SKU" wire:model.live.debounce.300ms="search" />
            <x-select label="Kategori" wire:model.live="categoryFilter">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-select>
        </div>

        <div wire:loading.delay wire:target="search,categoryFilter" class="skeleton skeleton--block"></div>

        <div wire:loading.remove wire:target="search,categoryFilter">
            @if ($products->isEmpty())
                <x-empty-state title="Belum ada menu. Tambah menu pertama." icon="inbox">
                    <x-button wire:click="create">Tambah Produk</x-button>
                </x-empty-state>
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>SKU</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr wire:key="product-{{ $product->id }}">
                                <td>
                                    @if ($product->photo_url)
                                        <img src="{{ $product->photo_url }}" alt="" class="product-thumb">
                                    @else
                                        <div class="product-thumb product-thumb--empty"><x-icon name="utensils" /></div>
                                    @endif
                                </td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name }}</td>
                                <td class="num">{{ \App\Support\Money::format($product->price) }}</td>
                                <td>
                                    @if ($product->is_active)
                                        <x-badge variant="success">Aktif</x-badge>
                                    @else
                                        <x-badge variant="neutral">Nonaktif</x-badge>
                                    @endif
                                </td>
                                <td class="table__actions">
                                    <x-button variant="secondary" wire:click="edit({{ $product->id }})" aria-label="Ubah {{ $product->name }}"><x-icon name="pencil" /></x-button>
                                    <x-button variant="ghost" wire:click="toggleActive({{ $product->id }})">
                                        {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </x-button>
                                    <x-button variant="ghost" wire:click="delete({{ $product->id }})" wire:confirm="Hapus produk {{ $product->name }}?">Hapus</x-button>
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
            <div class="card__title">{{ $editingId ? 'Ubah Produk' : 'Tambah Produk' }}</div>
            <form wire:submit="save" class="field-group">
                <div class="card__row">
                    <x-select label="Kategori" wire:model="category_id" :error="$errors->first('category_id')">
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="SKU" wire:model="sku" :error="$errors->first('sku')" />
                    <x-input label="Nama" wire:model="name" :error="$errors->first('name')" />
                    <x-input label="Harga (Rp)" type="number" min="0" inputmode="numeric" wire:model="price" :error="$errors->first('price')" />
                    <x-input label="Modal / HPP (Rp)" type="number" min="0" inputmode="numeric" wire:model="cost_price" :error="$errors->first('cost_price')" />
                </div>
                <p class="muted">Modal/HPP dipakai untuk hitung Keuntungan Bersih di Laporan — hanya berlaku bila produk ini tidak punya resep bahan baku di Stok (resep lebih presisi, otomatis dipakai bila ada).</p>
                <div class="card__row">
                    <label class="field">
                        <span class="field__label">Foto produk</span>
                        <input type="file" wire:model="photo" class="input" accept="image/*">
                        @error('photo') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" alt="" class="product-thumb product-thumb--lg">
                    @elseif ($image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image_path) }}" alt="" class="product-thumb product-thumb--lg">
                    @endif
                </div>
                <div class="card__row">
                    <x-toggle label="Tampil di kasir" wire:model="is_active" />
                    <x-toggle label="Lacak stok produk" wire:model.live="track_stock" />
                    @if ($track_stock)
                        <x-input label="Stok" type="number" min="0" wire:model="stock_qty" :error="$errors->first('stock_qty')" />
                    @endif
                </div>

                <div class="variant-editor">
                    <div class="card__header">
                        <div class="field__label">Varian</div>
                        <x-button type="button" variant="ghost" wire:click="addGroup"><x-icon name="plus" /> Tambah Grup Varian</x-button>
                    </div>

                    @foreach ($groups as $gi => $group)
                        <div class="variant-group" wire:key="group-{{ $gi }}-{{ $group['id'] ?? 'new' }}">
                            <div class="card__row">
                                <x-input label="Nama grup" placeholder="Ukuran" wire:model="groups.{{ $gi }}.name" :error="$errors->first('groups.'.$gi.'.name')" />
                                <x-input label="Maks. pilihan" type="number" min="1" wire:model="groups.{{ $gi }}.max_select" :error="$errors->first('groups.'.$gi.'.max_select')" />
                                <x-toggle label="Wajib dipilih" wire:model="groups.{{ $gi }}.is_required" />
                                <x-button type="button" variant="ghost" wire:click="removeGroup({{ $gi }})">Hapus Grup</x-button>
                            </div>

                            @foreach ($group['options'] as $oi => $option)
                                <div class="card__row" wire:key="option-{{ $gi }}-{{ $oi }}-{{ $option['id'] ?? 'new' }}">
                                    <x-input label="Opsi" placeholder="Large" wire:model="groups.{{ $gi }}.options.{{ $oi }}.name" :error="$errors->first('groups.'.$gi.'.options.'.$oi.'.name')" />
                                    <x-input label="Tambahan harga (Rp)" type="number" min="0" wire:model="groups.{{ $gi }}.options.{{ $oi }}.price_delta" :error="$errors->first('groups.'.$gi.'.options.'.$oi.'.price_delta')" />
                                    <x-button type="button" variant="ghost" wire:click="removeOption({{ $gi }}, {{ $oi }})" aria-label="Hapus opsi"><x-icon name="x" /></x-button>
                                </div>
                            @endforeach

                            <x-button type="button" variant="secondary" wire:click="addOption({{ $gi }})"><x-icon name="plus" /> Tambah Opsi</x-button>
                        </div>
                    @endforeach
                </div>

                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Simpan</x-button>
                    <x-button type="button" variant="secondary" wire:click="cancel">Batal</x-button>
                </div>
            </form>
        </div>
    @endif
</div>

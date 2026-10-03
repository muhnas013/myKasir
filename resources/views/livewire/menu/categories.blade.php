<div>
    <div class="card">
        <div class="card__header">
            <div class="card__title">Kategori</div>
            <x-button wire:click="create" wire:loading.attr="disabled"><x-icon name="plus" /> Tambah Kategori</x-button>
        </div>

        <div wire:loading.delay class="skeleton skeleton--block"></div>

        <div wire:loading.remove>
            @if ($categories->isEmpty())
                <x-empty-state title="Belum ada kategori. Tambah kategori pertama." icon="inbox" />
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Urutan</th>
                            <th>Produk</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr wire:key="category-{{ $category->id }}">
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->sort_order }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td class="table__actions">
                                    <x-button variant="secondary" wire:click="edit({{ $category->id }})" aria-label="Ubah {{ $category->name }}"><x-icon name="pencil" /></x-button>
                                    <x-button variant="ghost" wire:click="delete({{ $category->id }})" wire:confirm="Hapus kategori {{ $category->name }}?">Hapus</x-button>
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
            <div class="card__title">{{ $editingId ? 'Ubah Kategori' : 'Tambah Kategori' }}</div>
            <form wire:submit="save" class="field-group">
                <div class="card__row">
                    <x-input label="Nama" wire:model="name" :error="$errors->first('name')" />
                    <x-input label="Urutan" type="number" min="0" wire:model="sort_order" :error="$errors->first('sort_order')" />
                </div>
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Simpan</x-button>
                    <x-button type="button" variant="secondary" wire:click="cancel">Batal</x-button>
                </div>
            </form>
        </div>
    @endif
</div>

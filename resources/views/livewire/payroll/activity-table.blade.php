<div>
    <div class="card">
        <div class="card__header">
            <div class="card__title">Aktivitas Bonus</div>
            <x-button wire:click="create" wire:loading.attr="disabled"><x-icon name="plus" /> Tambah Aktivitas</x-button>
        </div>
        <p class="muted">Dipilih kasir saat tutup shift untuk mendapat bonus tambahan (06 P8).</p>

        <div wire:loading.delay class="skeleton skeleton--block"></div>

        <div wire:loading.remove>
            @if ($activities->isEmpty())
                <x-empty-state title="Belum ada aktivitas bonus. Tambah yang pertama." icon="inbox">
                    <x-button wire:click="create">Tambah Aktivitas</x-button>
                </x-empty-state>
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Bonus</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            <tr wire:key="activity-{{ $activity->id }}">
                                <td>{{ $activity->name }}</td>
                                <td class="num">{{ \App\Support\Money::format($activity->bonus_amount) }}</td>
                                <td>
                                    @if ($activity->is_active)
                                        <x-badge variant="success">Aktif</x-badge>
                                    @else
                                        <x-badge variant="neutral">Nonaktif</x-badge>
                                    @endif
                                </td>
                                <td class="table__actions">
                                    <x-button variant="secondary" wire:click="edit({{ $activity->id }})" aria-label="Ubah {{ $activity->name }}"><x-icon name="pencil" /></x-button>
                                    <x-button variant="ghost" wire:click="toggleActive({{ $activity->id }})">
                                        {{ $activity->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </x-button>
                                    <x-button variant="ghost" wire:click="delete({{ $activity->id }})" wire:confirm="Hapus aktivitas {{ $activity->name }}?">Hapus</x-button>
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
            <div class="card__title">{{ $editingId ? 'Ubah Aktivitas' : 'Tambah Aktivitas' }}</div>
            <form wire:submit="save" class="field-group">
                <div class="card__row">
                    <x-input label="Nama aktivitas" placeholder="Pembuatan jelly" wire:model="name" :error="$errors->first('name')" />
                    <x-input label="Bonus (Rp)" type="number" min="0" inputmode="numeric" wire:model="bonus_amount" :error="$errors->first('bonus_amount')" />
                </div>
                <div class="card__row">
                    <x-toggle label="Aktif" wire:model="is_active" />
                </div>
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Simpan</x-button>
                    <x-button type="button" variant="secondary" wire:click="cancel">Batal</x-button>
                </div>
            </form>
        </div>
    @endif
</div>

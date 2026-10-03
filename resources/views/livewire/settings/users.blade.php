<div>
    <div class="card">
        <div class="card__row" style="justify-content: space-between; align-items: center; margin-bottom: var(--sp-4);">
            <div class="card__title" style="margin-bottom: 0;">Pengguna</div>
            <x-button wire:click="create"><x-icon name="plus" /> Tambah Pengguna</x-button>
        </div>

        @if ($users->isEmpty())
            <x-empty-state title="Belum ada pengguna" icon="user" />
        @else
            <x-table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Peran</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td>{{ $user->email ?? '—' }}</td>
                            <td>
                                @if ($user->is_active)
                                    <x-badge variant="success">Aktif</x-badge>
                                @else
                                    <x-badge variant="neutral">Nonaktif</x-badge>
                                @endif
                            </td>
                            <td style="display:flex; gap: var(--sp-2);">
                                @if ($user->role->value !== 'owner')
                                    <x-button variant="secondary" wire:click="edit({{ $user->id }})"><x-icon name="pencil" /></x-button>
                                    <x-button variant="ghost" wire:click="toggleActive({{ $user->id }})">
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </x-button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif

        @error('is_active')
            <p class="field-error" style="margin-top: var(--sp-3);">{{ $message }}</p>
        @enderror
    </div>

    @if ($showForm)
        <div class="card">
            <div class="card__title">{{ $editingId ? 'Ubah Pengguna' : 'Tambah Pengguna' }}</div>
            <form wire:submit="save" class="field-group">
                <div class="card__row">
                    <x-input label="Nama" wire:model="name" :error="$errors->first('name')" />
                    <x-select label="Peran" wire:model.live="role" :error="$errors->first('role')">
                        <option value="cashier">Kasir</option>
                        <option value="admin">Admin</option>
                    </x-select>
                </div>
                @if ($role === 'admin')
                    <div class="card__row">
                        <x-input label="Email" type="email" wire:model="email" :error="$errors->first('email')" />
                        <x-input label="Kata sandi" type="password" wire:model="password" :error="$errors->first('password')" placeholder="{{ $editingId ? 'Biarkan kosong bila tidak diubah' : '' }}" />
                    </div>
                @endif
                <div class="card__row">
                    <x-input label="PIN (6 digit)" type="password" inputmode="numeric" maxlength="6" wire:model="pin" :error="$errors->first('pin')" placeholder="{{ $editingId ? 'Biarkan kosong bila tidak diubah' : '' }}" />
                    @if ($editingId)
                        <x-toggle label="Aktif" wire:model="is_active" />
                    @endif
                </div>
                <div class="card__row">
                    <x-button type="submit">Simpan</x-button>
                    <x-button type="button" variant="secondary" wire:click="cancel">Batal</x-button>
                </div>
            </form>
        </div>
    @endif
</div>

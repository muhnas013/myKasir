<div>
    <div class="app-topbar">
        <h1>Transaksi Hari Ini</h1>
    </div>

    <div class="card">
        <div wire:loading.delay class="skeleton skeleton--block"></div>

        <div wire:loading.remove>
            @if ($orders->isEmpty())
                <x-empty-state title="Belum ada transaksi hari ini." icon="inbox">
                    <a href="{{ route('pos.index') }}" class="btn btn--primary">Ke Kasir</a>
                </x-empty-state>
            @else
                <x-table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Waktu</th>
                            <th>Kasir</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr wire:key="order-{{ $order->id }}">
                                <td>{{ $order->number }}</td>
                                <td class="num">{{ $order->created_at->format('H.i') }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td class="num">{{ \App\Support\Money::format($order->total) }}</td>
                                <td>
                                    @if ($order->status->value === 'paid')
                                        <x-badge variant="success">Lunas</x-badge>
                                    @elseif ($order->status->value === 'open')
                                        <x-badge variant="warning">Tersimpan</x-badge>
                                    @else
                                        <x-badge variant="danger">Void</x-badge>
                                    @endif
                                </td>
                                <td class="table__actions">
                                    @if ($order->status->value === 'paid')
                                        <a href="{{ route('receipts.show', $order) }}" target="_blank" rel="noopener" class="btn btn--secondary">Struk</a>
                                        <x-button variant="ghost" wire:click="startVoid({{ $order->id }})">Void</x-button>
                                    @elseif ($order->status->value === 'open')
                                        @if ($order->shift->user_id === auth()->id() && $order->shift->isOpen())
                                            <a href="{{ route('pos.index', ['resume' => $order->id]) }}" class="btn btn--secondary">Lanjutkan</a>
                                        @endif
                                        <x-button variant="ghost" wire:click="discard({{ $order->id }})" wire:confirm="Batalkan pesanan {{ $order->number }}?">Batalkan</x-button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        </div>
    </div>

    @if ($voidingId)
        <x-modal title="Void Transaksi" close="cancelVoid">
            <form wire:submit="confirmVoid" class="field-group">
                <x-input label="Alasan (min. 5 karakter)" wire:model="reason" :error="$errors->first('reason')" />
                @if (auth()->user()->isCashier())
                    <x-input label="PIN admin/pemilik" type="password" inputmode="numeric" maxlength="6" autocomplete="off" wire:model="approver_pin" :error="$errors->first('approver_pin')" />
                @endif
                @error('order') <span class="field-error">{{ $message }}</span> @enderror
                <div class="card__row">
                    <x-button type="submit" variant="danger" wire:loading.attr="disabled">Void Transaksi</x-button>
                    <x-button type="button" variant="secondary" wire:click="cancelVoid">Batal</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>

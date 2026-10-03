<div class="cart">
    @if ($completed)
        <div class="cart__success">
            <x-icon name="check" class="cart__success-icon" />
            <div class="cart__success-title">Pembayaran berhasil</div>
            <div class="cart__success-number">{{ $completed->number }}</div>
            <dl class="summary">
                <div class="summary__row"><dt>Total</dt><dd>{{ \App\Support\Money::format($completed->total) }}</dd></div>
                @if ($completed->payment?->change_amount > 0)
                    <div class="summary__row summary__row--strong"><dt>Kembalian</dt><dd>{{ \App\Support\Money::format($completed->payment->change_amount) }}</dd></div>
                @endif
            </dl>
            <a href="{{ route('receipts.show', $completed) }}" target="_blank" rel="noopener" class="btn btn--secondary">Cetak Struk</a>
            <x-button type="button" wire:click="newOrder">Pesanan Baru</x-button>
        </div>
    @else
        <div class="cart__header">
            <div class="card__title">Pesanan</div>
            <x-segmented :options="['take_away' => 'Bawa Pulang', 'dine_in' => 'Makan di Tempat']" :current="$order_type" model="order_type" />
        </div>

        <x-input label="Nama / meja" placeholder="Opsional" maxlength="50" wire:model.blur="customer_label" />

        <div class="cart__lines" wire:loading.class="is-loading" wire:target="add,increment,decrement,remove">
            @if ($lines === [])
                <x-empty-state title="Belum ada pesanan. Ketuk menu di kiri." icon="store" />
            @elseif ($priced)
                @foreach ($priced['items'] as $i => $item)
                    @php($key = $lines[$i]['key'])
                    <div class="cart-line" wire:key="line-{{ $key }}">
                        <div class="cart-line__info">
                            <div class="cart-line__name">{{ $item['product_name'] }}</div>
                            @if ($item['options'])
                                <div class="cart-line__opts">{{ collect($item['options'])->pluck('name')->implode(', ') }}</div>
                            @endif
                            <div class="cart-line__price">{{ \App\Support\Money::format($item['line_total']) }}</div>
                        </div>
                        <div class="qty">
                            <button type="button" class="qty__btn" wire:click="decrement('{{ $key }}')" aria-label="Kurangi {{ $item['product_name'] }}">&minus;</button>
                            <span class="qty__value">{{ $item['qty'] }}</span>
                            <button type="button" class="qty__btn" wire:click="increment('{{ $key }}')" aria-label="Tambah {{ $item['product_name'] }}"><x-icon name="plus" /></button>
                        </div>
                    </div>
                @endforeach
            @else
                @foreach ($lines as $line)
                    <div class="cart-line" wire:key="line-{{ $line['key'] }}">
                        <div class="cart-line__info"><div class="cart-line__name">Item tidak tersedia</div></div>
                        <x-button type="button" variant="ghost" wire:click="remove('{{ $line['key'] }}')" aria-label="Hapus item"><x-icon name="x" /></x-button>
                    </div>
                @endforeach
            @endif
        </div>

        @if ($error)
            <p class="field-error">{{ $error }}</p>
        @endif
        @error('cart') <p class="field-error">{{ $message }}</p> @enderror
        @error('discount') <p class="field-error">{{ $message }}</p> @enderror

        @if ($priced)
            <dl class="summary">
                <div class="summary__row"><dt>Subtotal</dt><dd>{{ \App\Support\Money::format($priced['subtotal']) }}</dd></div>
                @if ($priced['discount'] > 0)
                    <div class="summary__row"><dt>Diskon</dt><dd>-{{ \App\Support\Money::format($priced['discount']) }}</dd></div>
                @endif
                @if ($priced['service'] > 0)
                    <div class="summary__row"><dt>Layanan</dt><dd>{{ \App\Support\Money::format($priced['service']) }}</dd></div>
                @endif
                @if ($priced['tax'] > 0)
                    <div class="summary__row"><dt>PB1</dt><dd>{{ \App\Support\Money::format($priced['tax']) }}</dd></div>
                @endif
                @if ($priced['rounding'] !== 0)
                    <div class="summary__row"><dt>Pembulatan</dt><dd>{{ \App\Support\Money::format($priced['rounding']) }}</dd></div>
                @endif
                <div class="summary__row summary__row--total"><dt>Total</dt><dd>{{ \App\Support\Money::format($priced['total']) }}</dd></div>
            </dl>
        @endif

        <div class="cart__actions">
            <x-button type="button" variant="secondary" wire:click="openDiscount" :disabled="$lines === []">Diskon</x-button>
            <x-button type="button" variant="secondary" wire:click="saveOpen" wire:loading.attr="disabled" :disabled="$lines === []">Simpan</x-button>
            <x-button type="button" class="btn--block btn--tall" wire:click="openPay" wire:loading.attr="disabled" :disabled="! $priced">Bayar</x-button>
        </div>
    @endif

    @if ($showDiscount)
        <x-modal title="Diskon" close="closeDiscount">
            <form wire:submit="applyDiscount" class="field-group">
                <x-segmented :options="['amount' => 'Nominal (Rp)', 'percent' => 'Persen (%)']" :current="$draft_discount_type" model="draft_discount_type" />
                <x-input label="Nilai diskon" type="number" min="0" inputmode="numeric" wire:model="draft_discount_value" :error="$errors->first('draft_discount_value')" />
                @if (auth()->user()->isCashier())
                    <x-input label="PIN admin/pemilik" type="password" inputmode="numeric" maxlength="6" autocomplete="off" wire:model="approver_pin" :error="$errors->first('approver_pin')" />
                @endif
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Terapkan</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDiscount">Batal</x-button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($showPay && $priced)
        <x-modal title="Pembayaran" close="closePay">
            <div class="pay-total">{{ \App\Support\Money::format($priced['total']) }}</div>

            <div class="segmented" role="group">
                @foreach ($methods as $value => $label)
                    <button type="button" class="segmented__item @if($method === $value) is-active @endif" aria-pressed="{{ $method === $value ? 'true' : 'false' }}" wire:click="$set('method', '{{ $value }}')">{{ $label }}</button>
                @endforeach
            </div>

            <form wire:submit="complete" class="field-group pay-form">
                @if ($method === 'cash')
                    <x-input label="Uang diterima (Rp)" type="number" min="0" inputmode="numeric" wire:model.live="paid_amount" :error="$errors->first('paid_amount')" />
                    <div class="quick-amounts">
                        <button type="button" class="btn btn--secondary" wire:click="setPaid({{ $priced['total'] }})">Uang Pas</button>
                        <button type="button" class="btn btn--secondary" wire:click="setPaid(50000)">50.000</button>
                        <button type="button" class="btn btn--secondary" wire:click="setPaid(100000)">100.000</button>
                    </div>
                    @php($change = (int) $paid_amount - $priced['total'])
                    <div class="summary__row summary__row--strong"><dt>Kembalian</dt><dd>{{ \App\Support\Money::format(max(0, $change)) }}</dd></div>
                @elseif ($method === 'qris')
                    @if ($qrisUrl)
                        <img src="{{ $qrisUrl }}" alt="QRIS outlet" class="qris-image">
                    @else
                        <x-empty-state title="Gambar QRIS belum diatur." icon="inbox" />
                    @endif
                    <p class="muted">Tekan "Sudah Diterima" setelah dana masuk.</p>
                @else
                    <x-input label="Nomor referensi" maxlength="50" wire:model="reference" :error="$errors->first('reference')" />
                @endif

                @error('cart') <span class="field-error">{{ $message }}</span> @enderror
                @error('method') <span class="field-error">{{ $message }}</span> @enderror

                <div class="card__row">
                    <x-button type="submit" class="btn--tall" wire:loading.attr="disabled" :disabled="$method === 'cash' && (int) $paid_amount < $priced['total']">
                        {{ $method === 'qris' ? 'Sudah Diterima' : 'Selesaikan Pembayaran' }}
                    </x-button>
                    <x-button type="button" variant="secondary" wire:click="closePay">Batal</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>

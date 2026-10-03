<div>
    <div class="app-topbar">
        <h1>Tutup Shift</h1>
    </div>

    @if ($closed)
        <div class="card card--narrow" id="shift-summary">
            <div class="card__title">Ringkasan shift</div>
            <dl class="summary">
                <div class="summary__row"><dt>Modal awal</dt><dd>{{ \App\Support\Money::format($closed->opening_cash) }}</dd></div>
                <div class="summary__row"><dt>Kas seharusnya</dt><dd>{{ \App\Support\Money::format($closed->expected_cash) }}</dd></div>
                <div class="summary__row"><dt>Kas dihitung</dt><dd>{{ \App\Support\Money::format($closed->counted_cash) }}</dd></div>
                <div class="summary__row summary__row--strong">
                    <dt>Selisih</dt>
                    <dd>
                        @if ($closed->cash_difference === 0)
                            <x-badge variant="success">Sesuai</x-badge>
                        @else
                            <x-badge variant="danger">{{ \App\Support\Money::format($closed->cash_difference) }}</x-badge>
                        @endif
                    </dd>
                </div>
                @if ($closed->closing_note)
                    <div class="summary__row"><dt>Catatan</dt><dd>{{ $closed->closing_note }}</dd></div>
                @endif
            </dl>
            <div class="card__row">
                <x-button type="button" variant="secondary" x-on:click="window.print()">Cetak Ringkasan</x-button>
                <a href="{{ route('shift.open') }}" class="btn btn--primary">Buka Shift Baru</a>
            </div>
        </div>
    @else
        <div class="card card--narrow">
            <div class="card__title">Hitung kas fisik</div>
            <form wire:submit="save" class="field-group">
                <x-input label="Uang tunai di laci (Rp)" type="number" min="0" inputmode="numeric" wire:model="counted_cash" :error="$errors->first('counted_cash')" />
                <x-input label="Catatan (wajib bila ada selisih)" wire:model="closing_note" :error="$errors->first('closing_note')" />
                @error('shift')
                    <span class="field-error">{{ $message }}</span>
                @enderror
                <div class="card__row">
                    <x-button type="submit" wire:loading.attr="disabled">Tutup Shift</x-button>
                    <a href="{{ route('pos.index') }}" class="btn btn--secondary">Batal</a>
                </div>
            </form>
        </div>
    @endif
</div>

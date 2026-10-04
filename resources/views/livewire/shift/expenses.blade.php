<div>
    <div class="app-topbar">
        <h1>Pengeluaran Kas</h1>
    </div>

    <div class="card card--narrow">
        <div class="card__title">Catat pengeluaran</div>
        <p class="muted">Untuk keperluan outlet selama shift (mis. beli es batu, gula) — mengurangi kas yang diharapkan saat tutup shift.</p>
        <form wire:submit="save" class="field-group">
            <x-input label="Keterangan" placeholder="Es batu" wire:model="description" :error="$errors->first('description')" />
            <x-input label="Nominal (Rp)" type="number" min="0" inputmode="numeric" wire:model="amount" :error="$errors->first('amount')" />
            <x-button type="submit" wire:loading.attr="disabled">Simpan</x-button>
        </form>
    </div>

    <div class="card card--narrow">
        <div class="card__title">Pengeluaran shift ini</div>
        @if ($expenses->isEmpty())
            <x-empty-state title="Belum ada pengeluaran dicatat." icon="wallet" />
        @else
            @foreach ($expenses as $expense)
                <div class="cart-line" wire:key="expense-{{ $expense->id }}">
                    <div class="cart-line__info">
                        <div class="cart-line__name">{{ $expense->description }}</div>
                        <div class="cart-line__price">{{ \App\Support\Money::format($expense->amount) }}</div>
                    </div>
                    <x-button type="button" variant="ghost" wire:click="delete({{ $expense->id }})" wire:confirm="Hapus pengeluaran {{ $expense->description }}?" aria-label="Hapus"><x-icon name="x" /></x-button>
                </div>
            @endforeach
            <dl class="summary">
                <div class="summary__row summary__row--total"><dt>Total pengeluaran</dt><dd>{{ \App\Support\Money::format($expenses->sum('amount')) }}</dd></div>
            </dl>
        @endif
    </div>

    <div class="card__row">
        <a href="{{ route('pos.index') }}" class="btn btn--secondary">Kembali ke Kasir</a>
    </div>
</div>

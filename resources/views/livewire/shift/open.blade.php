<div>
    <div class="app-topbar">
        <h1>Buka Shift</h1>
    </div>

    <div class="card card--narrow">
        <div class="card__title">Modal awal</div>
        <form wire:submit="save" class="field-group">
            <x-input label="Uang tunai di laci (Rp)" type="number" min="0" inputmode="numeric" wire:model="opening_cash" :error="$errors->first('opening_cash')" />
            <x-input label="PIN Anda (konfirmasi — 6 digit)" type="password" inputmode="numeric" maxlength="6" autocomplete="off" wire:model="pin" :error="$errors->first('pin')" />
            <div class="card__row">
                <x-button type="submit" wire:loading.attr="disabled">Buka Shift</x-button>
            </div>
        </form>
    </div>
</div>

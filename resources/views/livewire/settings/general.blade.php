<div>
    <form wire:submit="save">
        <div class="card">
            <div class="card__title">Profil Outlet</div>
            <div class="card__row">
                <x-input label="Nama outlet" wire:model="outlet_name" :error="$errors->first('outlet_name')" />
                <x-input label="Telepon" wire:model="outlet_phone" :error="$errors->first('outlet_phone')" />
            </div>
            <div class="card__row" style="margin-top: var(--sp-4);">
                <x-input label="Alamat" wire:model="outlet_address" :error="$errors->first('outlet_address')" />
            </div>
            <div class="card__row" style="margin-top: var(--sp-4);">
                <label class="field">
                    <span class="field__label">Logo</span>
                    <input type="file" wire:model="logo" class="input" accept="image/*">
                    @if ($logo_path)
                        <span class="field-error" style="color: var(--c-text-muted);">Tersimpan: {{ $logo_path }}</span>
                    @endif
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card__title">Pajak &amp; Biaya Layanan</div>
            <x-toggle label="PB1 aktif" wire:model.live="tax_enabled" />
            @if ($tax_enabled)
                <div class="card__row" style="margin-top: var(--sp-3);">
                    <x-input label="Tarif PB1 (%)" type="number" step="0.1" wire:model="tax_rate" :error="$errors->first('tax_rate')" />
                </div>
            @endif
            <div style="margin-top: var(--sp-4);">
                <x-toggle label="Biaya layanan aktif" wire:model.live="service_enabled" />
            </div>
            @if ($service_enabled)
                <div class="card__row" style="margin-top: var(--sp-3);">
                    <x-input label="Tarif biaya layanan (%)" type="number" step="0.1" wire:model="service_rate" :error="$errors->first('service_rate')" />
                </div>
            @endif
            <div class="card__row" style="margin-top: var(--sp-4);">
                <x-input label="Pembulatan ke (Rp)" type="number" wire:model="rounding_unit" :error="$errors->first('rounding_unit')" />
            </div>
        </div>

        <div class="card">
            <div class="card__title">Metode Bayar Aktif</div>
            <x-toggle label="Tunai" wire:model="method_cash" />
            <x-toggle label="QRIS" wire:model.live="method_qris" />
            @if ($method_qris)
                <label class="field" style="margin-top: var(--sp-3);">
                    <span class="field__label">Gambar QRIS</span>
                    <input type="file" wire:model="qris_image" class="input" accept="image/*">
                    @if ($qris_image_path)
                        <span class="field-error" style="color: var(--c-text-muted);">Tersimpan: {{ $qris_image_path }}</span>
                    @endif
                </label>
            @endif
            <x-toggle label="Debit/Transfer" wire:model="method_card" />
        </div>

        <div class="card">
            <div class="card__title">Teks Struk</div>
            <textarea class="input" wire:model="receipt_footer" rows="3" placeholder="Terima kasih atas kunjungan Anda"></textarea>
        </div>

        <x-button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-button>
    </form>
</div>

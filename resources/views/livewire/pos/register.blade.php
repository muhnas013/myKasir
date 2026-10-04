<div
    x-data="offlinePos(@js($offlineCatalog), @js($offlineSettings), @js(auth()->user()->activeShift()->id))"
    x-init="init()"
    class="pos-wrapper"
    :class="{ 'is-printing-receipt': completed }"
>
    <div class="app-topbar">
        <h1>Kasir</h1>
        <div class="app-topbar__status">
            <x-badge x-show="online" variant="success" x-cloak>Online</x-badge>
            <x-badge x-show="!online" variant="warning" x-cloak>
                Offline — mode darurat <span x-show="queueCount > 0" x-text="'· ' + queueCount + ' menunggu sinkron'"></span>
            </x-badge>
        </div>
    </div>

    <div class="pos" x-show="online" x-cloak>
        <div class="pos__menu">
            @livewire('pos.grid')
        </div>
        <div class="pos__cart">
            @livewire('pos.cart')
        </div>
    </div>

    <div class="pos" x-show="!online && !completed" x-cloak>
        <div class="pos__menu">
            <template x-if="catalog.length === 0">
                <x-empty-state title="Tidak ada produk tanpa varian yang bisa dijual offline." icon="inbox" />
            </template>
            <div class="menu-grid" x-show="catalog.length > 0">
                <template x-for="product in catalog" :key="product.id">
                    <button type="button" class="menu-tile" @click="openProduct(product)">
                        <span class="menu-tile__name" x-text="product.name"></span>
                        <span class="menu-tile__price" x-text="formatRp(product.price)"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="pos__cart">
            <div class="cart">
                <div class="cart__header">
                    <div class="card__title">Pesanan (Offline — Tunai saja)</div>
                    <div class="segmented" role="group">
                        <button type="button" class="segmented__item" :class="{ 'is-active': orderType === 'take_away' }" @click="orderType = 'take_away'">Bawa Pulang</button>
                        <button type="button" class="segmented__item" :class="{ 'is-active': orderType === 'dine_in' }" @click="orderType = 'dine_in'">Makan di Tempat</button>
                    </div>
                </div>

                <x-input label="Nama / meja" placeholder="Opsional" maxlength="50" x-model="customerLabel" />

                <div class="cart__lines">
                    <template x-if="lines.length === 0">
                        <x-empty-state title="Belum ada pesanan. Ketuk menu di kiri." icon="store" />
                    </template>
                    <template x-for="(line, index) in lines" :key="line.product_id">
                        <div class="cart-line">
                            <div class="cart-line__info">
                                <div class="cart-line__name" x-text="line.name"></div>
                                <div class="cart-line__price" x-text="formatRp(line.price * line.qty)"></div>
                            </div>
                            <div class="qty">
                                <button type="button" class="qty__btn" @click="decrement(index)" aria-label="Kurangi">&minus;</button>
                                <span class="qty__value" x-text="line.qty"></span>
                                <button type="button" class="qty__btn" @click="increment(index)" aria-label="Tambah"><x-icon name="plus" /></button>
                            </div>
                        </div>
                    </template>
                </div>

                <dl class="summary" x-show="lines.length > 0">
                    <div class="summary__row"><dt>Subtotal</dt><dd x-text="formatRp(priced.subtotal)"></dd></div>
                    <div class="summary__row" x-show="priced.service > 0"><dt>Layanan</dt><dd x-text="formatRp(priced.service)"></dd></div>
                    <div class="summary__row" x-show="priced.tax > 0"><dt>PB1</dt><dd x-text="formatRp(priced.tax)"></dd></div>
                    <div class="summary__row" x-show="priced.rounding !== 0"><dt>Pembulatan</dt><dd x-text="formatRp(priced.rounding)"></dd></div>
                    <div class="summary__row summary__row--total"><dt>Total (estimasi)</dt><dd x-text="formatRp(priced.total)"></dd></div>
                </dl>

                <x-input label="Uang diterima (Rp)" type="number" min="0" inputmode="numeric" x-model.number="paidAmount" />
                <div class="quick-amounts">
                    <button type="button" class="btn btn--secondary" @click="paidAmount = priced.total">Uang Pas</button>
                    <button type="button" class="btn btn--secondary" @click="paidAmount = 50000">50.000</button>
                    <button type="button" class="btn btn--secondary" @click="paidAmount = 100000">100.000</button>
                </div>

                <x-button type="button" class="btn--block btn--tall" @click="pay()" x-bind:disabled="lines.length === 0 || paidAmount < priced.total">
                    Bayar Tunai (Offline)
                </x-button>
            </div>
        </div>
    </div>

    <div class="offline-receipt-print" x-show="completed" x-cloak>
        <div class="cart__success">
            <x-icon name="check" class="cart__success-icon" />
            <div class="cart__success-title">Tersimpan offline</div>
            <p class="muted">Belum tersinkron ke server — akan terkirim otomatis saat online.</p>

            <article class="receipt">
                <header class="receipt__header">
                    <div class="receipt__outlet">ESTIMASI — BELUM SINKRON</div>
                </header>
                <div class="receipt__rule"></div>
                <template x-for="item in completed ? completed.items : []" :key="item.product_id">
                    <div class="receipt__line">
                        <span x-text="item.qty + ' x ' + item.name"></span>
                        <span x-text="formatRp(item.price * item.qty)"></span>
                    </div>
                </template>
                <div class="receipt__rule"></div>
                <div class="receipt__line receipt__line--total"><span>Total</span><span x-text="completed ? formatRp(completed.total) : ''"></span></div>
                <div class="receipt__line"><span>Bayar (Tunai)</span><span x-text="completed ? formatRp(completed.paid) : ''"></span></div>
                <div class="receipt__line"><span>Kembali</span><span x-text="completed ? formatRp(completed.change) : ''"></span></div>
            </article>

            <x-button type="button" variant="secondary" @click="printReceipt()">Cetak Struk</x-button>
            <x-button type="button" @click="newOrder()">Pesanan Baru</x-button>
        </div>
    </div>

    {{-- Modal pilih varian offline — Alpine murni, bukan <x-modal> (terikat $wire, 06 P7 butuh vanilla JS). --}}
    <div class="modal" x-show="selectingProduct" x-cloak x-on:keydown.escape.window="closeProduct()">
        <div class="modal__backdrop" @click="closeProduct()"></div>
        <div class="modal__dialog" role="dialog" aria-modal="true" x-trap.noscroll="selectingProduct !== null">
            <template x-if="selectingProduct">
                <div>
                    <div class="card__title" x-text="selectingProduct.name"></div>
                    <template x-for="group in selectingProduct.variant_groups" :key="group.id">
                        <fieldset class="option-group">
                            <legend class="field__label">
                                <span x-text="group.name"></span>
                                <x-badge x-show="group.is_required" variant="warning" x-cloak>Wajib</x-badge>
                            </legend>
                            <div class="option-list">
                                <template x-for="option in group.options" :key="option.id">
                                    <label class="option-item">
                                        <template x-if="group.max_select === 1">
                                            <input type="radio" :name="'group-' + group.id" :value="option.id" x-model="selected[group.id]">
                                        </template>
                                        <template x-if="group.max_select !== 1">
                                            <input type="checkbox" :value="option.id" x-model="selected[group.id]">
                                        </template>
                                        <span x-text="option.name"></span>
                                        <span class="option-item__price" x-show="option.price_delta > 0" x-text="'+ ' + formatRp(option.price_delta)"></span>
                                    </label>
                                </template>
                            </div>
                            <span class="field-error" x-show="selectError[group.id]" x-text="selectError[group.id]"></span>
                        </fieldset>
                    </template>
                    <div class="card__row">
                        <x-button type="button" @click="confirmOptions()">Tambah</x-button>
                        <x-button type="button" variant="secondary" @click="closeProduct()">Batal</x-button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

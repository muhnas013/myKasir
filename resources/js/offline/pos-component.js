// Alpine component untuk mode kasir offline (F6, docs/06 P7). Dipasang via
// alpine:init agar terdaftar sebelum Alpine milik Livewire mulai (resources/js/app.js).

import { estimateTotal } from './pricer';
import * as queue from './queue';
import { syncQueue } from './sync';

function formatRp(amount) {
    return 'Rp ' + Math.round(amount).toLocaleString('id-ID');
}

export default function offlinePos(catalog, settings, shiftId) {
    return {
        online: navigator.onLine,
        catalog,
        settings,
        shiftId,
        lines: [],
        orderType: 'take_away',
        customerLabel: '',
        note: '',
        paidAmount: 0,
        completed: null,
        queueCount: 0,
        selectingProduct: null,
        selected: {},
        selectError: {},

        init() {
            window.addEventListener('online', () => { this.online = true; });
            window.addEventListener('offline', () => { this.online = false; });
            window.addEventListener('offline-queue-changed', () => this.refreshQueueCount());
            this.refreshQueueCount();
        },

        formatRp,

        async refreshQueueCount() {
            this.queueCount = await queue.count();
        },

        // Produk tanpa grup varian langsung masuk keranjang; produk bervarian
        // buka modal pilih opsi dulu (cermin App\Livewire\Pos\Grid::selectProduct).
        openProduct(product) {
            if (product.variant_groups.length === 0) {
                this.addToCart(product, []);

                return;
            }

            this.selected = {};
            for (const group of product.variant_groups) {
                this.selected[group.id] = group.max_select === 1 ? '' : [];
            }
            this.selectError = {};
            this.selectingProduct = product;
        },

        closeProduct() {
            this.selectingProduct = null;
            this.selected = {};
            this.selectError = {};
        },

        // Cermin App\Livewire\Pos\Grid::confirmOptions — wajib & max_select
        // divalidasi di sini untuk UX; server (CartPricer) tetap menolak ulang
        // bila divalidasi dilewati atau dimanipulasi.
        confirmOptions() {
            const product = this.selectingProduct;
            const errors = {};
            const chosen = [];

            for (const group of product.variant_groups) {
                const raw = this.selected[group.id] ?? (group.max_select === 1 ? '' : []);
                const picked = (Array.isArray(raw) ? raw : [raw])
                    .filter((id) => id !== '' && id !== null && id !== undefined)
                    .map((id) => Number(id));
                const validIds = new Set(group.options.map((o) => o.id));

                if (picked.some((id) => !validIds.has(id))) {
                    errors[group.id] = 'Pilihan tidak valid.';
                    continue;
                }
                if (group.is_required && picked.length === 0) {
                    errors[group.id] = 'Pilih salah satu ' + group.name.toLowerCase() + '.';
                    continue;
                }
                if (picked.length > group.max_select) {
                    errors[group.id] = 'Maksimal ' + group.max_select + ' pilihan.';
                    continue;
                }

                for (const id of picked) {
                    chosen.push(group.options.find((o) => o.id === id));
                }
            }

            if (Object.keys(errors).length > 0) {
                this.selectError = errors;

                return;
            }

            this.addToCart(product, chosen);
            this.closeProduct();
        },

        /** @param {{id:number,name:string,price_delta:number}[]} options */
        addToCart(product, options) {
            const optionIds = options.map((o) => o.id);
            const unitPrice = product.price + options.reduce((sum, o) => sum + o.price_delta, 0);
            const name = options.length > 0 ? `${product.name} (${options.map((o) => o.name).join(', ')})` : product.name;

            const existing = this.lines.find((l) => l.product_id === product.id
                && JSON.stringify(l.option_ids) === JSON.stringify(optionIds));
            if (existing) {
                existing.qty += 1;
            } else {
                this.lines.push({ product_id: product.id, option_ids: optionIds, name, price: unitPrice, qty: 1 });
            }
        },

        increment(index) {
            this.lines[index].qty += 1;
        },

        decrement(index) {
            this.lines[index].qty -= 1;
            if (this.lines[index].qty < 1) {
                this.lines.splice(index, 1);
            }
        },

        get priced() {
            return estimateTotal(this.lines, this.settings, this.orderType === 'dine_in');
        },

        async pay() {
            if (this.lines.length === 0 || Number(this.paidAmount) < this.priced.total) {
                return;
            }

            const priced = this.priced;
            const order = {
                idempotency_key: crypto.randomUUID(),
                shift_id: this.shiftId,
                lines: this.lines.map((l) => ({ product_id: l.product_id, option_ids: l.option_ids, qty: l.qty })),
                order_type: this.orderType,
                customer_label: this.customerLabel,
                note: this.note,
                method: 'cash',
                paid_amount: Number(this.paidAmount),
                client_estimated_total: priced.total,
            };

            await queue.enqueue(order);
            await this.refreshQueueCount();

            this.completed = {
                total: priced.total,
                paid: Number(this.paidAmount),
                change: Number(this.paidAmount) - priced.total,
                items: this.lines.map((l) => ({ ...l })),
            };

            this.lines = [];
            this.orderType = 'take_away';
            this.customerLabel = '';
            this.note = '';
            this.paidAmount = 0;

            syncQueue();
        },

        newOrder() {
            this.completed = null;
        },

        printReceipt() {
            window.print();
        },
    };
}

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

        addToCart(product) {
            const existing = this.lines.find((l) => l.product_id === product.id);
            if (existing) {
                existing.qty += 1;
            } else {
                this.lines.push({ product_id: product.id, name: product.name, price: product.price, qty: 1 });
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
                lines: this.lines.map((l) => ({ product_id: l.product_id, option_ids: [], qty: l.qty })),
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

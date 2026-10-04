// Replay antrean offline ke endpoint sinkronisasi (docs/06 P7). Dipanggil saat online
// kembali dan berkala selagi online, agar transaksi yang masuk saat tab lain/offline
// sebelumnya ikut tersinkron begitu koneksi pulih.

import * as queue from './queue';

const RETRY_MS = 20000;
let syncing = false;

function toast(type, message) {
    window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
}

async function syncOne(order) {
    const response = await window.axios.post('/pos/offline-sync', order);

    return response.data;
}

export async function syncQueue() {
    if (syncing || !navigator.onLine) {
        return;
    }
    syncing = true;

    try {
        const pending = (await queue.all()).filter((o) => !o._syncFailed);

        for (const order of pending) {
            try {
                const result = await syncOne(order);
                await queue.remove(order.idempotency_key);
                toast('success', `Pesanan offline ${result.number} tersinkron.`);
                window.dispatchEvent(new CustomEvent('offline-queue-changed'));
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    // Gagal permanen (mis. shift sudah ditutup, stok habis) — bukan masalah
                    // koneksi. Simpan sebagai gagal agar tidak diulang tanpa henti; kasir
                    // harus menghubungi pemilik (docs/16_DEBUGGING_GUIDE.md).
                    await queue.enqueue({ ...order, _syncFailed: true, _syncError: error.response.data?.message ?? 'Ditolak server.' });
                    toast('danger', 'Satu transaksi offline gagal disinkronkan. Hubungi pemilik.');
                    window.dispatchEvent(new CustomEvent('offline-queue-changed'));
                }
                // Error jaringan: biarkan di antrean, dicoba lagi pada siklus berikutnya.
            }
        }
    } finally {
        syncing = false;
    }
}

export function startAutoSync() {
    if (navigator.onLine) {
        syncQueue();
    }
    window.addEventListener('online', syncQueue);
    setInterval(syncQueue, RETRY_MS);
}

// Antrean transaksi offline (F6, docs/06 P7) — IndexedDB, bukan Background Sync API
// (dukungan browser tidak konsisten, lih. docs/09_STACK.md). keyPath = idempotency_key
// agar dipakai ulang persis sebagai kunci dedup saat sinkron.

const DB_NAME = 'mykasir-offline';
const STORE = 'orders_queue';

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = () => {
            req.result.createObjectStore(STORE, { keyPath: 'idempotency_key' });
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function withStore(mode, fn) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const store = tx.objectStore(STORE);
        const result = fn(store);
        tx.oncomplete = () => resolve(result);
        tx.onerror = () => reject(tx.error);
    });
}

export async function enqueue(order) {
    await withStore('readwrite', (store) => store.put(order));
}

export async function all() {
    return withStore('readonly', (store) => new Promise((resolve, reject) => {
        const req = store.getAll();
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    }));
}

export async function remove(idempotencyKey) {
    await withStore('readwrite', (store) => store.delete(idempotencyKey));
}

export async function count() {
    const items = await all();

    return items.length;
}

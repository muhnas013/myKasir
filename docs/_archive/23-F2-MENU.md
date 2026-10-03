# 23 (arsip) — F2 — MENU

Diterima: 2026-10-04. Penegakan pindah ke test suite (`tests/Feature/Menu/*`, `tests/Feature/Pos/MenuGridTest.php`).

- [x] Given produk `is_active=false`, Then tidak tampil di grid kasir. (`MenuGridTest`)
- [x] Given grup varian wajib, When produk diketuk, Then dialog varian muncul dan item tak bisa ditambah tanpa memilih. (`MenuGridTest`)
- [x] Given perubahan harga produk, Then `audit_logs.action=product.price_changed` berisi harga lama & baru. (`ProductTest`) — klausa "`order_items` lama tidak berubah" dipindah ke F3 karena `order_items` baru ada di F3.
- [x] Layar Menu menampilkan empat state (26) — kosong: "Belum ada menu. Tambah menu pertama."

```
php artisan test --filter=Menu → lulus: 26 passed, 0 failures
```

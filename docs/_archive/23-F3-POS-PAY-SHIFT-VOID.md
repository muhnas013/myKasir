# 23 (arsip) — F3 — POS + PAY + SHIFT + VOID

Diterima (butir otomatis): 2026-10-04. Penegakan pindah ke test suite. Dua butir manual (cetak 58 mm, stopwatch 30 detik) tetap aktif di `docs/23`.

- [x] (dari F2) Harga produk diubah setelah ada pesanan → `order_items` lama tidak berubah. (`CompleteOrderTest::test_old_order_items_keep_price_after_product_price_change`)
- [x] Keranjang 2× Es Kopi + 1 Nasi Goreng + 1 Pisang Goreng, PB1 10%, take away → subtotal 73.000, tax 7.300, total 80.300. (`PriceCalculatorTest`, `CompleteOrderTest`)
- [x] Dine in, layanan 5% → service 3.650, tax 7.665, total 84.300, rounding −15. (`PriceCalculatorTest`, `CompleteOrderTest`)
- [x] Tunai 100.000 → kembalian 19.700; selesai ditolak bila uang < total (tombol nonaktif + validasi server). (`CompleteOrderTest`, `RegisterTest`)
- [x] Debit/Transfer tanpa nomor referensi → ditolak. (`CompleteOrderTest`)
- [x] Dua panggilan `CompleteOrder` dengan `idempotency_key` sama → tepat 1 baris `orders`. (`CompleteOrderTest`, `RegisterTest`)
- [x] Stok tidak cukup saat bayar → tidak ada baris baru di `orders`/`payments`/`stock_movements`; keranjang tetap. (`CompleteOrderTest`, `RegisterTest`)
- [x] Kasir tanpa shift `open` tidak bisa membuka layar kasir. (`RegisterTest`)
- [x] Tutup shift: modal 200.000 + tunai 80.300 → expected_cash 280.300; dihitung 280.000 → selisih −300, catatan wajib. (`ShiftActionsTest`, `ShiftPagesTest`)
- [x] Void oleh kasir tanpa PIN admin ditolak; dengan PIN → void, stok kembali, expected_cash berkurang. (`VoidOrderTest`, `HistoryAndReceiptTest`)
- [x] Struk memuat nama outlet, nomor, waktu WITA, item, subtotal, PB1, total, bayar, kembali, footer (isi HTML). (`HistoryAndReceiptTest`)

```
php artisan test --filter='CompleteOrder|VoidOrder|Shift|PriceCalculator' → lulus: 57 passed, 0 failures
php artisan test → 119 passed, 0 failures
```

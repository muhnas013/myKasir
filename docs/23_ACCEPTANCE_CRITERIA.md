# 23 — ACCEPTANCE_CRITERIA

Hanya fitur AKTIF. Fitur yang diterima dipindah ke `docs/_archive/23-{kode}.md`. Fitur: `docs/01_PRD.md`; proses & rumus: `docs/06_BUSINESS_PROCESS.md`; peran: `docs/05_USER_ROLE.md`; DoD: `docs/24_DEFINITION_OF_DONE.md`.
Data uji standar: Es Kopi Susu Gula Aren Rp 18.000, Nasi Goreng Spesial Rp 25.000, Pisang Goreng Rp 12.000.

## F1 — AUTH + SET
- [ ] Given kasir aktif ber-PIN, When memilih nama + PIN benar, Then masuk ke layar Buka Shift/Kasir.
- [ ] Given PIN salah 5x, Then dialog terkunci 15 menit dan baris `audit_logs.action=auth.pin_locked` tercipta.
- [ ] Given cashier login, When membuka `/settings` atau `/menu`, Then 403.
- [ ] Given owner terakhir, When mencoba menonaktifkan dirinya, Then ditolak dengan pesan.
- [ ] Given owner mengubah PB1 jadi 11%, Then pesanan baru memakai 11% dan perubahan tercatat `setting.updated`.
```
php artisan test --filter='Auth|Settings'   → lulus: 0 failures
```

## F2 — MENU
- [ ] Given produk `is_active=false`, Then tidak tampil di grid kasir.
- [ ] Given grup varian wajib, When produk diketuk, Then dialog varian muncul dan item tak bisa ditambah tanpa memilih.
- [ ] Given perubahan harga produk, Then `audit_logs.action=product.price_changed` berisi harga lama & baru, dan `order_items` lama tidak berubah.
- [ ] Layar Menu menampilkan empat state (26) — kosong: "Belum ada menu. Tambah menu pertama."
```
php artisan test --filter=Menu   → lulus: 0 failures
```

## F3 — POS + PAY + SHIFT + VOID
- [ ] Keranjang 2× Es Kopi + 1 Nasi Goreng + 1 Pisang Goreng, PB1 10%, layanan off, take away → subtotal 73.000, tax 7.300, total **80.300**.
- [ ] Sama, dine in, layanan 5% on → service 3.650, tax 7.665, total **84.300**, rounding −15.
- [ ] Tunai 100.000 → kembalian 19.700; tombol selesai nonaktif bila uang < total.
- [ ] Debit/Transfer tanpa nomor referensi → ditolak.
- [ ] Dua panggilan `CompleteOrder` dengan `idempotency_key` sama → tepat 1 baris `orders`.
- [ ] Stok tidak cukup saat bayar → tidak ada baris baru di `orders`/`payments`/`stock_movements`; keranjang tetap.
- [ ] Kasir tanpa shift `open` tidak bisa membuka layar kasir.
- [ ] Tutup shift: modal 200.000 + 1 transaksi tunai 80.300 → `expected_cash` 280.300; dihitung 280.000 → selisih −300, catatan wajib.
- [ ] Void oleh kasir tanpa PIN admin → ditolak; dengan PIN → status `void`, stok kembali, `expected_cash` berkurang.
- [ ] Struk menampilkan nama outlet, nomor, waktu WITA, item, subtotal, PB1, total, bayar, kembali, footer — tercetak penuh di kertas 58 mm (manual).
- [ ] Alur 3 item tunai selesai ≤ 30 detik oleh penguji yang belum pernah memakai (manual, stopwatch).
```
php artisan test --filter='CompleteOrder|VoidOrder|Shift|PriceCalculator'   → lulus: 0 failures
```

## F4 — STOCK
- [ ] Jual 1 Es Kopi Susu (resep: espresso 18 g, susu 120 ml, gula aren 25 ml) → tiap bahan berkurang sesuai, 3 baris `stock_movements` tipe `sale`.
- [ ] Stok masuk susu 5.000 ml total Rp 90.000 saat stok 2.000 ml @ avg_cost 17.000/1000 ml → `avg_cost` baru = (2.000×17 + 90.000) ÷ 7.000 ×1000 = **17.714** per 1000 ml (dibulatkan half-up).
- [ ] Bahan dengan `stock_qty ≤ min_qty` tampil di banner "hampir habis".
- [ ] Opname tanpa alasan → ditolak; dengan alasan → baris `adjustment`.
- [ ] Produk yang bahannya tidak cukup untuk 1 porsi tampil "Habis" di kasir.
```
php artisan test --filter='Stock|Recipe'   → lulus: 0 failures
```

## F5 — REPORT
- [ ] Omzet hari ini = Σ total order `paid` dengan `business_date` hari ini (void tidak dihitung).
- [ ] Grafik per jam memakai jam Asia/Makassar; transaksi 23.50 masuk tanggal yang benar.
- [ ] Export Excel berisi kolom: No, Waktu, Kasir, Item, Metode, Subtotal, Diskon, Layanan, PB1, Total, Status; jumlah baris = jumlah order periode.
- [ ] Kasir hanya melihat ringkasan shift sendiri.
```
php artisan test --filter=Report   → lulus: 0 failures
```

## Verifikasi UI (semua fase)
- [ ] `grep -rnE "#[0-9A-Fa-f]{3,6}\b" resources/views resources/js` → 0 hasil (warna hanya dari `resources/css/app.css`).
- [ ] Tiap halaman ber-data punya empat state (26).

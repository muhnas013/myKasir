# 23 — ACCEPTANCE_CRITERIA

Hanya fitur AKTIF. Fitur yang diterima dipindah ke `docs/_archive/23-{kode}.md`. Fitur: `docs/01_PRD.md`; proses & rumus: `docs/06_BUSINESS_PROCESS.md`; peran: `docs/05_USER_ROLE.md`; DoD: `docs/24_DEFINITION_OF_DONE.md`.
Data uji standar: Es Kopi Susu Gula Aren Rp 18.000, Nasi Goreng Spesial Rp 25.000, Pisang Goreng Rp 12.000.

## F3 — POS + PAY + SHIFT + VOID (sisa: uji manual)
Butir otomatis sudah diterima dan diarsipkan di `docs/_archive/23-F3-POS-PAY-SHIFT-VOID.md`. Sisa butir manual:
- [ ] Struk tercetak penuh di kertas 58 mm pada printer thermal nyata (nama outlet, nomor, waktu WITA, item, subtotal, PB1, total, bayar, kembali, footer) (manual).
- [ ] Alur 3 item tunai selesai ≤ 30 detik oleh penguji yang belum pernah memakai (manual, stopwatch).

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

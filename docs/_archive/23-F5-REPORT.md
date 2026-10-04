# 23 (arsip) — F5 — REPORT

Diterima: 2026-10-04. Penegakan pindah ke test suite.

- [x] Omzet hari ini = Σ total order `paid` dengan `business_date` hari ini (void tidak dihitung). (`ReportTest::test_omzet_hari_ini_menjumlahkan_order_lunas_dan_mengabaikan_void`)
- [x] Grafik per jam memakai jam Asia/Makassar; transaksi 23.50 masuk tanggal yang benar. (`ReportTest::test_grafik_per_jam_memakai_jam_asia_makassar`)
- [x] Export Excel berisi kolom: No, Waktu, Kasir, Item, Metode, Subtotal, Diskon, Layanan, PB1, Total, Status; jumlah baris = jumlah order periode (paid + void). (`ReportTest::test_export_excel_berisi_kolom_dan_jumlah_baris_sesuai_periode`)
- [x] Kasir hanya melihat ringkasan miliknya sendiri (diterapkan sebagai filter per-user lintas periode, bukan per-shift tunggal — lihat deviasi di ledger); tombol unduh Excel hanya untuk owner/admin. (`ReportTest::test_kasir_hanya_melihat_data_sendiri_dan_tanpa_tombol_unduh`, `test_kasir_tidak_bisa_mengakses_export`)

```
php artisan test --filter=Report → 6 passed, 0 failures
php artisan test → 144 passed, 0 failures
```

# 03 — ROADMAP

Setiap fase = vertical slice (migrasi → Action/Service → Livewire/Blade → tes) yang bisa didemokan. Fase berikutnya dimulai setelah kriteria fase sebelumnya di `docs/23_ACCEPTANCE_CRITERIA.md` lulus.

| Fase | Tujuan yang bisa didemokan | Fitur (kode di 01) | Status |
|---|---|---|---|
| F1 | Pemilik login, mengisi profil outlet, pajak & metode bayar, membuat akun kasir ber-PIN; kasir login dengan PIN | AUTH, SET | selesai |
| F2 | Admin membuat kategori, produk, dan varian; produk aktif muncul di grid kasir (belum bisa bayar) | MENU | selesai |
| F3 | Kasir buka shift → transaksi → bayar Tunai/QRIS/Debit → cetak struk → tutup shift dengan selisih kas; void dengan PIN admin | POS, PAY, SHIFT, VOID | |
| F4 | Admin mengisi bahan baku & resep; penjualan memotong stok; stok masuk memperbarui HPP; peringatan menipis tampil | STOCK | |
| F5 | Pemilik membuka laporan hari ini/7/30 hari/rentang tanggal dan mengunduh Excel | REPORT | |
| F6 (setelah MVP) | Kasir tetap bisa transaksi saat internet putus; transaksi tersinkron tanpa duplikat | OFFLINE | |

Catatan F3: stok produk (`track_stock`) sudah dipotong di F3; stok bahan baku baru aktif di F4.

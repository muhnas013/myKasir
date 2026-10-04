# 23 — ACCEPTANCE_CRITERIA

Hanya fitur AKTIF. Fitur yang diterima dipindah ke `docs/_archive/23-{kode}.md`. Fitur: `docs/01_PRD.md`; proses & rumus: `docs/06_BUSINESS_PROCESS.md`; peran: `docs/05_USER_ROLE.md`; DoD: `docs/24_DEFINITION_OF_DONE.md`.
Data uji standar: Es Kopi Susu Gula Aren Rp 18.000, Nasi Goreng Spesial Rp 25.000, Pisang Goreng Rp 12.000.

## F3 — POS + PAY + SHIFT + VOID (sisa: uji manual)
Butir otomatis sudah diterima dan diarsipkan di `docs/_archive/23-F3-POS-PAY-SHIFT-VOID.md`. Sisa butir manual:
- [ ] Struk tercetak penuh di kertas 58 mm pada printer thermal nyata (nama outlet, nomor, waktu WITA, item, subtotal, PB1, total, bayar, kembali, footer) (manual).
- [ ] Alur 3 item tunai selesai ≤ 30 detik oleh penguji yang belum pernah memakai (manual, stopwatch).

## F6 — OFFLINE (sisa: uji manual browser)
Butir sinkron/dedup/audit/potong-stok sudah dibuktikan `OfflineSyncTest` (hijau) dan diarsipkan di `docs/_archive/23-F6-OFFLINE.md`. Sisa butir berikut butuh browser nyata — 13_TESTING: tak ada framework tes JS di proyek ini, jadi belum bisa diklaim terbukti hanya dari membaca kode:
- [ ] Saat koneksi diputus (DevTools → Network → Offline), layar kasir tetap menampilkan menu dan menerima transaksi Tunai untuk produk tanpa grup varian (produk bervarian tampil nonaktif "butuh koneksi"); QRIS, Debit/Transfer, dan diskon tidak tersedia (manual).
- [ ] Transaksi offline tersimpan ke IndexedDB; struk menampilkan tanda "Estimasi — belum sinkron" (manual).
- [ ] Badge status online/offline tampil di layar kasir memakai token warna 26 (manual + `grep -rnE "#[0-9A-Fa-f]{3,6}\b" resources/views resources/js` → 0 hasil).
- [ ] Me-refresh layar kasir saat offline tidak menampilkan halaman error browser, app-shell termuat dari cache Service Worker (manual).
- [ ] Antre 2 transaksi Tunai saat offline, online kembali, kedua order muncul di `/orders` tanpa duplikat dan tanpa aksi manual kasir (manual, end-to-end).

## Verifikasi UI (semua fase)
- [ ] `grep -rnE "#[0-9A-Fa-f]{3,6}\b" resources/views resources/js` → 0 hasil (warna hanya dari `resources/css/app.css`).
- [ ] Tiap halaman ber-data punya empat state (26).

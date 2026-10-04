# 23 — ACCEPTANCE_CRITERIA

Hanya fitur AKTIF. Fitur yang diterima dipindah ke `docs/_archive/23-{kode}.md`. Fitur: `docs/01_PRD.md`; proses & rumus: `docs/06_BUSINESS_PROCESS.md`; peran: `docs/05_USER_ROLE.md`; DoD: `docs/24_DEFINITION_OF_DONE.md`.
Data uji standar: Es Kopi Susu Gula Aren Rp 18.000, Nasi Goreng Spesial Rp 25.000, Pisang Goreng Rp 12.000.

## F3 — POS + PAY + SHIFT + VOID (sisa: uji manual)
Butir otomatis sudah diterima dan diarsipkan di `docs/_archive/23-F3-POS-PAY-SHIFT-VOID.md`. Sisa butir manual:
- [ ] Struk tercetak penuh di kertas 58 mm pada printer thermal nyata (nama outlet, nomor, waktu WITA, item, subtotal, PB1, total, bayar, kembali, footer) (manual).
- [ ] Alur 3 item tunai selesai ≤ 30 detik oleh penguji yang belum pernah memakai (manual, stopwatch).

## F6 — OFFLINE (sisa: uji manual browser)
Butir sinkron/dedup/audit/potong-stok sudah dibuktikan `OfflineSyncTest` (hijau) dan diarsipkan di `docs/_archive/23-F6-OFFLINE.md`. Sisa butir berikut butuh browser nyata — 13_TESTING: tak ada framework tes JS di proyek ini, jadi belum bisa diklaim terbukti hanya dari membaca kode:
- [ ] Saat koneksi diputus (DevTools → Network → Offline), layar kasir tetap menampilkan menu dan menerima transaksi Tunai (termasuk produk bervarian — lihat iterasi 2 di bawah); QRIS, Debit/Transfer, dan diskon tidak tersedia (manual).
- [ ] Transaksi offline tersimpan ke IndexedDB; struk menampilkan tanda "Estimasi — belum sinkron" (manual).
- [ ] Badge status online/offline tampil di layar kasir memakai token warna 26 (manual + `grep -rnE "#[0-9A-Fa-f]{3,6}\b" resources/views resources/js` → 0 hasil).
- [ ] Me-refresh layar kasir saat offline tidak menampilkan halaman error browser, app-shell termuat dari cache Service Worker (manual).
- [ ] Antre 2 transaksi Tunai saat offline, online kembali, kedua order muncul di `/orders` tanpa duplikat dan tanpa aksi manual kasir (manual, end-to-end).

### F6 — iterasi 2: varian offline (06 P7 rule 1)
Diskon dan QRIS/Debit offline tetap ditunda (butuh verifikasi/konfirmasi yang bergantung koneksi — lihat `02_SCOPE.md` F6). Varian:
- [x] Katalog offline memuat `variant_groups` + opsi (id, nama, wajib, max_select, `price_delta`) untuk tiap produk aktif (`RegisterTest::test_offline_catalog_includes_variant_groups_and_options`).
- [x] Sinkron dengan opsi terpilih menghitung ulang total termasuk `price_delta` di server, bukan ditebak klien (`OfflineSyncTest::test_sync_with_selected_variant_option_prices_with_delta`).
- [x] Grup wajib tanpa opsi terpilih ditolak saat sinkron (422), order tidak tercatat (`OfflineSyncTest::test_sync_with_missing_required_variant_option_is_rejected`).
- [ ] Manual (DevTools → Offline): produk bervarian bisa diketuk di layar kasir, modal pilih opsi muncul (bukan "butuh koneksi" lagi), grup wajib tak bisa dilewati ("Tambah" tertahan dengan pesan error), opsi dengan harga tambahan tampil `+ Rp ...`, dan struk estimasi menampilkan nama varian terpilih.

## F7 — WAGE, slice 1: skema + kontrol admin aktivitas (06 P8)
- [x] Pemilik bisa tambah/ubah/nonaktifkan/hapus aktivitas bonus (nama + nominal) di halaman Penggajian (`WageActivityTest::test_owner_can_create_activity`, `test_owner_can_edit_activity`, `test_owner_can_toggle_active_status`, `test_owner_can_delete_activity`).
- [x] Admin dan kasir tidak bisa mengakses halaman/komponen Penggajian (`WageActivityTest::test_admin_cannot_access_payroll_page`, `test_admin_cannot_access_activity_component`, `test_cashier_cannot_access_activity_component`).
- [x] Validasi nominal bonus (`test_bonus_amount_is_validated`), state kosong (`test_empty_state_is_shown_without_activities`).
- [ ] Slice 2 (belum): kasir mencatat aktivitas saat tutup shift.
- [ ] Slice 3 (belum): `WageCalculator` (upah dasar + bonus aktivitas + bonus penjualan per hari) dan halaman Rekap Upah.

## Verifikasi UI (semua fase)
- [ ] `grep -rnE "#[0-9A-Fa-f]{3,6}\b" resources/views resources/js` → 0 hasil (warna hanya dari `resources/css/app.css`).
- [ ] Tiap halaman ber-data punya empat state (26).

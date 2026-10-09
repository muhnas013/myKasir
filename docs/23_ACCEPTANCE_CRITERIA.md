# 23 — ACCEPTANCE_CRITERIA

Hanya fitur AKTIF. Fitur yang diterima dipindah ke `docs/_archive/23-{kode}.md`. Fitur: `docs/01_PRD.md`; proses & rumus: `docs/06_BUSINESS_PROCESS.md`; peran: `docs/05_USER_ROLE.md`; DoD: `docs/24_DEFINITION_OF_DONE.md`.
Data uji standar: Es Kopi Susu Gula Aren Rp 18.000, Nasi Goreng Spesial Rp 25.000, Pisang Goreng Rp 12.000.

## POS — ukuran grid & mode daftar Kasir (26, tambahan setelah F3 diarsipkan)
- [x] Toggle ukuran grid (Kecil/Sedang/Besar) + mode Grid/Daftar tersedia di layar Kasir online & offline; preferensi tersimpan per-perangkat (`localStorage`), bukan di server — tidak ada tes PHP (13: tak ada framework tes JS), diverifikasi manual via screenshot headless (desktop & mobile 375px, 4 kombinasi, overflow 0px).
- [ ] Manual: pilih mode/ukuran di satu perangkat, refresh halaman — preferensi tetap tersimpan (localStorage per origin).

## REPORT/MENU — Modal/HPP & Keuntungan Bersih (06 P5, tambahan setelah F2/F5 diarsipkan)
- [x] Produk punya field Modal/HPP; tersimpan lewat form Menu (`ProductTest::test_admin_can_set_cost_price`).
- [x] Produk BERRESEP tetap pakai HPP presisi dari bahan baku; Modal/HPP manual hanya fallback saat tanpa resep (`RecipeManagementTest::test_cost_price_is_used_as_fallback_hpp_when_no_recipe`, `test_recipe_cost_takes_precedence_over_cost_price`).
- [x] Laporan menampilkan Total HPP & Keuntungan Bersih = Omzet − HPP (`ReportTest::test_keuntungan_bersih_mengurangi_hpp_dari_omzet`).
- [x] Kasir (report.view-own) tidak melihat angka HPP/Keuntungan Bersih — hanya owner/admin (`test_cashier_does_not_see_profit_figures`, `test_owner_sees_profit_figures`).

## SHIFT — konfirmasi PIN sendiri saat Buka Shift (06 P2, tambahan setelah F3 diarsipkan)
- [x] Buka Shift menolak tanpa PIN benar milik kasir yang login (`ShiftPagesTest::test_cashier_opens_shift_with_opening_cash`, `test_wrong_pin_blocks_opening_shift`).
- [x] PIN salah 5x berturut-turut mengunci 15 menit + audit `auth.pin_locked`, sama seperti PIN persetujuan (`ShiftActionsTest::test_five_wrong_own_pins_lock_and_audit`).
- [x] Tidak mengganti sesi/identitas — hanya mencocokkan ke PIN milik user yang sedang login (`VerifyOwnPin`, bukan pencarian lintas-user seperti `VerifyApproverPin`).

## SHIFT — pengeluaran kas operasional (06 P2, tambahan setelah F3 diarsipkan)
- [x] Kasir mencatat pengeluaran (keterangan + nominal) selama shift terbuka; mengurangi `expected_cash` (`ShiftActionsTest::test_expense_reduces_expected_cash`).
- [x] Pengeluaran tak bisa dicatat pada shift tertutup atau oleh bukan pemilik shift (`test_expense_cannot_be_recorded_on_closed_shift`, `test_expense_cannot_be_recorded_by_another_user`).
- [x] Kasir bisa hapus pengeluaran yang salah catat selama shift masih terbuka (`ShiftPagesTest::test_cashier_can_record_and_delete_expense`).
- [x] Ringkasan Tutup Shift menampilkan total pengeluaran bila ada (visual, lihat `close.blade.php`).

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

## F7 — WAGE, slice 2: kasir catat aktivitas saat tutup shift (06 P8)
- [x] Layar Tutup Shift menampilkan daftar aktivitas aktif sebagai checklist opsional (`ShiftPagesTest::test_cashier_can_pick_activities_while_closing_shift`).
- [x] Aktivitas nonaktif tidak bisa ikut tercatat meski id-nya dikirim manual (`ShiftActionsTest::test_selected_activities_are_logged_with_snapshot`).
- [x] Nama & nominal di-snapshot ke `shift_activities` saat shift ditutup; perubahan katalog setelahnya tidak mengubah baris yang sudah tercatat (test yang sama).

## F7 — WAGE, slice 3: kalkulasi upah + Rekap Upah (06 P8)
- [x] Upah dasar per shift closed (`WageCalculatorTest::test_base_wage_only_when_below_sales_threshold`).
- [x] Bonus penjualan berjenjang per hari, lintas shift/pegawai, cocok dengan contoh terdokumentasi — 350rb+450rb sehari → 15rb+20rb (`WageCalculatorTest::test_matches_documented_example_two_shifts_same_day`).
- [x] Shift di hari berbeda tak ikut terpool untuk ambang minimum (`test_same_revenue_split_across_different_days_does_not_pool`).
- [x] Bonus aktivitas masuk ke total upah (`test_activity_bonus_is_included_in_total`); shift masih `open` tak dihitung (`test_open_shift_is_excluded`).
- [x] Halaman Rekap Upah (owner-only): ringkasan total per pegawai, filter periode (Hari Ini/7/30/Rentang, pola sama dengan Laporan), **tanpa periode default** — tabel baru tampil setelah periode dipilih eksplisit (`WageActivityTest::test_wage_summary_requires_period_before_showing_rows`, `test_wage_summary_shows_empty_state_without_closed_shifts`, `test_admin_cannot_access_wage_summary_component`).
- [ ] Manual: uji dengan data produksi nyata sebelum dipakai membayar gaji sungguhan.

## F7 — WAGE, slice 4: export Excel Rekap Upah, satu baris per karyawan (tambahan setelah slice 3)
- [x] Rekap Upah (tabel di layar & Excel) ditampilkan **satu baris per karyawan** untuk periode terpilih — omzet, upah dasar, bonus aktivitas, bonus penjualan, total dijumlah lintas shift karyawan itu, bukan per shift (`WageCalculatorTest::test_summary_by_user_aggregates_total`, `WageExportTest::test_beberapa_shift_karyawan_yang_sama_digabung_jadi_satu_baris`).
- [x] Pemilik bisa unduh Rekap Upah sebagai Excel dari halaman Penggajian, kolom & jumlah baris sesuai periode yang dipilih (`WageExportTest::test_owner_bisa_unduh_excel_rekap_upah`).
- [x] Admin dan kasir tidak bisa mengakses endpoint export (`WageExportTest::test_admin_tidak_bisa_mengakses_export_penggajian`, `test_kasir_tidak_bisa_mengakses_export_penggajian`).
- [x] Guest diarahkan ke login (`WageExportTest::test_guest_diarahkan_ke_login`).

## Verifikasi UI (semua fase)
- [ ] `grep -rnE "#[0-9A-Fa-f]{3,6}\b" resources/views resources/js` → 0 hasil (warna hanya dari `resources/css/app.css`).
- [ ] Tiap halaman ber-data punya empat state (26).

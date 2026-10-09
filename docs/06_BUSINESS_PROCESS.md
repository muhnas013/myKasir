# 06 — BUSINESS_PROCESS

Peran: `docs/05_USER_ROLE.md`. Tabel/kolom: `docs/07_DATA_MODEL.md`.

## P1 — Transaksi kasir (POS + PAY)
1. Kasir login PIN. Bila belum punya shift `open`, ia diarahkan ke "Buka Shift" (P2) dan tidak bisa membuka layar kasir.
2. Kasir mengetuk produk. Bila produk punya grup varian wajib, dialog varian muncul dulu. Item yang sama dengan opsi yang sama digabung (qty +1).
3. Kasir memilih jenis pesanan (`dine_in`/`take_away`), mengisi nama/meja (opsional), catatan, dan diskon (P4 bila perlu persetujuan).
4. Sistem menghitung total sesuai **Aturan perhitungan** di bawah.
5. Kasir memilih metode bayar → dialog bayar:
   - Tunai: `paid_amount ≥ total`, kembalian = `paid_amount − total`.
   - QRIS: tampil gambar QRIS outlet; kasir menekan "Sudah diterima" setelah melihat notifikasi dana masuk.
   - Debit/Transfer: wajib isi nomor referensi.
6. "Selesaikan Pembayaran" menjalankan satu transaksi DB: buat `orders` (`paid`), `order_items`, `payments`, kurangi stok (P5), catat `stock_movements`.
7. Sistem menampilkan layar sukses → "Cetak Struk" (window.print, 58 mm) / "Pesanan Baru".

**Kondisi gagal**
- Produk dengan stok efektif 0 tampil "Habis" dan tidak bisa diketuk.
- Tunai kurang → tombol selesaikan nonaktif.
- Stok berubah jadi tidak cukup di antara memilih dan membayar → transaksi dibatalkan seluruhnya (rollback), pesan "Stok {produk} tidak cukup", keranjang tetap ada.
- Klik ganda "Selesaikan" → hanya satu order tercipta (idempotency key per keranjang).

## P2 — Buka & tutup shift
1. Buka: kasir mengisi modal awal (≥ 0) + konfirmasi PIN miliknya sendiri (6 digit, `VerifyOwnPin`) → `shifts.status=open` tercatat atas nama kasir yang login. PIN salah ditolak; 5x salah dikunci 15 menit (21) — sama seperti PIN persetujuan.
2. Selama shift berjalan, kasir boleh mencatat pengeluaran kas operasional (mis. beli es batu) di `/shift/expenses` — nominal + keterangan, bisa dihapus selama shift masih terbuka. Mengurangi `expected_cash` (lihat 3).
3. Tutup: kasir menghitung uang fisik per pecahan atau total → sistem menghitung `expected_cash` (modal awal + tunai bersih dari penjualan − total pengeluaran kas) dan `cash_difference`.
4. Bila selisih ≠ 0, catatan wajib diisi. Shift berstatus `closed` (pengeluaran tak bisa diubah lagi setelah ini), ringkasan shift dicetak.
**Kondisi gagal:** masih ada pesanan `open` → tutup ditolak sampai pesanan dibayar atau dibatalkan.

### P2a — Jam operasional & batas shift (tambahan)
1. Shift (shift pertama hari itu sekalipun) hanya bisa dibuka pukul **08:00–21:59**; di luar jam itu `OpenShift` menolak dengan pesan "Outlet buka jam 08:00–22:00."
2. Maksimal **2 shift per hari untuk seluruh outlet** (lintas kasir, dihitung dari `opened_at` tanggal kalender) — begitu 2 shift hari itu sudah dibuka, percobaan shift ke-3 ditolak sampai besok jam 08:00.
3. Shift yang masih `open` jam 22:00 ditutup paksa otomatis oleh scheduler (`php artisan shifts:auto-close`, dijadwalkan `dailyAt('22:00')` di `routes/console.php`): `counted_cash` disamakan dengan `expected_cash` (selisih 0) + `closing_note` otomatis menandai "belum dihitung fisik", `closed_by` tetap `null` (bukan manusia) — dicatat ke `audit_logs` (`action=shift.auto_closed`). Shift dengan pesanan `open` yang belum selesai **dilewati** (tidak dipaksa tutup), perlu peninjauan manual pemilik.
**Kondisi gagal:** di luar jam operasional atau sudah 2 shift hari itu → `opening_cash` ditolak dengan pesan spesifik.

## P3 — Void transaksi
1. Kasir/admin memilih transaksi `paid` di shift yang masih `open`, lalu mengisi alasan (wajib, ≥ 5 karakter).
2. Bila pemicu `cashier` → dialog PIN owner/admin.
3. Status menjadi `void`; stok dikembalikan lewat `stock_movements` tipe `void_return`; penerimaan tunai keluar dari `expected_cash`.
**Kondisi gagal:** PIN salah → ditolak; 5x salah dalam 15 menit → persetujuan PIN dikunci 15 menit. Transaksi dari shift `closed` hanya bisa di-void oleh owner.

## P4 — Diskon
Diskon nominal atau persen per transaksi, tidak boleh membuat subtotal setelah diskon < 0. Diskon oleh `cashier` butuh PIN owner/admin.

## P5 — Potong stok
- Produk dengan resep: kurangi tiap bahan sebesar qty resep × qty item (+ resep opsi varian yang dipilih).
- Produk tanpa resep dengan `track_stock=true`: kurangi `products.stock_qty`.
- Stok boleh negatif hanya bila setting `allow_negative_stock=true` (default `false`).
- Bahan dengan `stock_qty ≤ min_qty` muncul di peringatan "hampir habis" (layar Menu & Stok dan badge di kasir).
- **HPP per porsi** (`StockService::porsiCost`, dicatat ke `order_items.unit_cost` tiap penjualan): produk BERRESEP dihitung dari `Σ qty resep × avg_cost bahan` (presisi); produk TANPA resep pakai `products.cost_price` (Modal/HPP manual diisi admin) sebagai HPP flat — dipakai Laporan untuk Keuntungan Bersih (= Omzet − Σ `unit_cost × qty`, lih. `07`).

## P6 — Stok masuk & opname
- Stok masuk: bahan, qty > 0, harga beli total → `avg_cost` baru = (stok lama × avg lama + harga beli total) ÷ (stok lama + qty).
- Opname: input stok fisik → sistem mencatat selisih sebagai `adjustment` + alasan.

## P7 — Mode offline & sinkronisasi (F6)
1. Saat koneksi putus, layar kasir (Alpine/vanilla JS, bukan Livewire) tetap menerima transaksi untuk **produk tanpa grup varian sama sekali** (produk dengan varian, wajib maupun opsional, ditandai "Pilih varian — butuh koneksi" dan tidak bisa diketuk offline, sama pola dengan tampilan "Habis"), tanpa diskon (P4 butuh persetujuan PIN yang sama-sama butuh koneksi untuk verifikasi), metode bayar Tunai saja (QRIS/Debit butuh konfirmasi manual yang mengandalkan referensi luar, ditahan sampai online — lihat Kondisi gagal). [ASUMSI cakupan F6 pertama: varian & diskon offline ditunda ke iterasi berikutnya — di luar slice ini.]
2. Order offline dihitung sementara di klien pakai tarif pajak/biaya/pembulatan yang di-cache saat login terakhir (aturan sama persis dengan "Aturan perhitungan" di bawah), disimpan ke IndexedDB dengan `idempotency_key` yang sama dipakai nanti saat sinkron.
3. Struk tercetak offline diberi tanda "Estimasi — belum sinkron".
4. Saat online kembali, antrean dikirim satu per satu ke endpoint sinkronisasi tipis (controller baru, BUKAN Livewire — Livewire butuh koneksi aktif per aksi) yang memanggil `Action` yang sama dipakai alur online (`CompleteOrder`), bukan logika bayar baru; server menghitung ulang via `PriceCalculator` dan memotong stok (P5) di titik sinkron, bukan di titik transaksi dibuat.
5. Klien menyertakan total estimasinya sebagai field informasional (`client_estimated_total`) — tidak pernah dipakai untuk kalkulasi. Bila berbeda dari hasil `CompleteOrder`, dicatat ke `audit_logs` (`action=order.offline_adjusted`, pola sama dengan `order.void`); total yang berlaku selalu hasil server.

**Kondisi gagal**
- Stok tidak cukup saat sinkron (dua kasir offline sama-sama menjual item terakhir) → `CompleteOrder` menolak (rollback) persis seperti alur online; order itu TIDAK tercatat dan tetap tersangkut di antrean lokal device yang mengirimnya (ditandai gagal, lihat 16). Kasir sudah menerima tunai tapi transaksi belum pernah ada di sistem — perlu ditinjau manual oleh pemilik (koreksi kas/kompensasi di luar sistem), bukan dibiarkan "stok negatif otomatis".
- QRIS/Debit-Transfer tidak bisa diselesaikan offline (butuh konfirmasi dana/referensi real-time) → tombol metode tersebut nonaktif saat offline, hanya Tunai yang bisa.
- Dua tab/device mengirim `idempotency_key` sama saat sinkron → server menolak duplikat (constraint unique sudah ada di 07), sinkron berikutnya menandai "sudah tersimpan" tanpa error ke kasir.

## P8 — Penggajian (F7)
1. Upah dihitung per shift **berstatus `closed`** (shift yang masih `open` belum ikut dihitung).
2. `upah_shift` = `wage_base_per_shift` (setting, default Rp35.000) + Σ `bonus_amount` pada `shift_activities` milik shift itu + `bonus_penjualan_shift` (langkah 3-5).
3. Kelompokkan shift closed berdasarkan tanggal kalender `opened_at` (hari) — **lintas pegawai**, bukan per pegawai.
4. `total_omzet_hari` = Σ omzet tiap shift dalam kelompok hari itu, dengan omzet shift = Σ `orders.total` WHERE `shift_id` = X AND `status` = `paid` (definisi sama dengan `ReportService`, void tidak dihitung).
5. Jika `total_omzet_hari` < `wage_sales_bonus_min_revenue` (setting, default Rp800.000) → `bonus_penjualan_shift` = 0 untuk semua shift hari itu. Jika ≥, untuk TIAP shift hari itu: `omzet_shift_dibulatkan` = `floor(omzet_shift / 100.000) * 100.000`; `bonus_penjualan_shift` = `(omzet_shift_dibulatkan / 100.000) * wage_sales_bonus_per_100k` (setting, default Rp5.000).
   - Contoh: shift A omzet 350.000, shift B omzet 450.000 di hari yang sama → total 800.000 (≥ minimum) → bonus didapat. Shift A dibulatkan ke 300.000 → bonus Rp15.000. Shift B dibulatkan ke 400.000 → bonus Rp20.000.
6. Aktivitas tambahan (mis. "Pembuatan Jelly") dipilih kasir sendiri di layar Tutup Shift dari daftar `wage_activities` aktif (dikontrol Pemilik di halaman Penggajian) — tanpa persetujuan admin. Nama & nominal di-snapshot ke `shift_activities` saat dicatat; perubahan katalog setelahnya tidak mengubah riwayat upah yang sudah tercatat.

## Aturan perhitungan (urutan tetap)
1. `subtotal` = Σ (harga produk + Σ tambahan opsi) × qty
2. `discount` → `net` = subtotal − discount
3. `service` = net × service_rate (hanya `dine_in` dan bila diaktifkan)
4. `tax` = (net + service) × tax_rate (bila PB1 aktif)
5. `total` = pembulatan ke Rp 100 terdekat (half-up) dari net + service + tax; selisih disimpan di `rounding`
Semua nilai integer Rupiah; tiap langkah dibulatkan `round()` half-up ke Rupiah.

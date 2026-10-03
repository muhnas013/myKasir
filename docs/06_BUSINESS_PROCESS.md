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
1. Buka: kasir mengisi modal awal (≥ 0) → `shifts.status=open`.
2. Tutup: kasir menghitung uang fisik per pecahan atau total → sistem menghitung `expected_cash` dan `cash_difference`.
3. Bila selisih ≠ 0, catatan wajib diisi. Shift berstatus `closed`, ringkasan shift dicetak.
**Kondisi gagal:** masih ada pesanan `open` → tutup ditolak sampai pesanan dibayar atau dibatalkan.

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

## P6 — Stok masuk & opname
- Stok masuk: bahan, qty > 0, harga beli total → `avg_cost` baru = (stok lama × avg lama + harga beli total) ÷ (stok lama + qty).
- Opname: input stok fisik → sistem mencatat selisih sebagai `adjustment` + alasan.

## Aturan perhitungan (urutan tetap)
1. `subtotal` = Σ (harga produk + Σ tambahan opsi) × qty
2. `discount` → `net` = subtotal − discount
3. `service` = net × service_rate (hanya `dine_in` dan bila diaktifkan)
4. `tax` = (net + service) × tax_rate (bila PB1 aktif)
5. `total` = pembulatan ke Rp 100 terdekat (half-up) dari net + service + tax; selisih disimpan di `rounding`
Semua nilai integer Rupiah; tiap langkah dibulatkan `round()` half-up ke Rupiah.

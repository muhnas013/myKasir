# 01 — PRD

## Daftar fitur
| Kode | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| AUTH | Autentikasi & peran | Login email+kata sandi (owner/admin), login PIN 6 digit (cashier), logout, kunci layar | MVP |
| SET | Pengaturan | Profil outlet, pajak/biaya, metode bayar aktif, gambar QRIS, teks struk, pengguna | MVP |
| MENU | Menu & varian | Kategori, produk, harga, Modal/HPP manual, varian (grup + opsi + tambahan harga), aktif/nonaktif di kasir | MVP |
| POS | Transaksi kasir | Pilih menu, varian, qty, catatan, diskon, makan di tempat/bawa pulang, nama/meja, simpan (open bill sederhana) | MVP |
| PAY | Pembayaran & struk | Tunai (kembalian), QRIS statis (konfirmasi manual), Debit/Transfer (no. referensi); cetak struk 58 mm | MVP |
| SHIFT | Shift kas | Buka shift dengan modal awal, catat pengeluaran kas operasional selama shift, tutup shift dengan hitung kas fisik & selisih | MVP |
| VOID | Pembatalan | Batalkan transaksi lunas dengan alasan + PIN admin; stok dikembalikan | MVP |
| STOCK | Bahan baku & resep | Bahan baku, resep per produk/opsi varian, potong stok otomatis, stok masuk, opname, HPP, peringatan menipis | MVP |
| REPORT | Laporan | Omzet, transaksi, rata-rata, Total HPP & Keuntungan Bersih (owner/admin), per jam, terlaris, metode bayar, riwayat, export Excel | MVP |
| OFFLINE | Mode offline (PWA) | Antrean transaksi di browser + sinkronisasi | Nanti (F6) |
| WAGE | Penggajian pegawai | Upah dasar per shift + bonus aktivitas (dikontrol pemilik) + bonus penjualan bertingkat per hari | Nanti (F7) |

## User story (MVP)
- **AUTH** — Sebagai kasir, saya ingin masuk cukup dengan PIN agar ganti kasir antar shift cepat.
- **SET** — Sebagai pemilik, saya ingin mengatur PB1, biaya layanan, dan pembulatan agar total di struk sesuai aturan outlet.
- **MENU** — Sebagai admin, saya ingin menambah varian berbayar (Large +Rp 5.000) agar kasir tidak mengetik harga manual.
- **POS** — Sebagai kasir, saya ingin mengetuk menu lalu langsung melihat total agar transaksi ≤ 30 detik.
- **PAY** — Sebagai kasir, saya ingin tombol nominal cepat (uang pas, 50rb, 100rb) agar kembalian dihitung otomatis.
- **SHIFT** — Sebagai pemilik, saya ingin melihat selisih kas setiap tutup shift agar kebocoran kas terdeteksi.
- **VOID** — Sebagai pemilik, saya ingin setiap pembatalan butuh PIN admin dan alasan agar pembatalan tidak disalahgunakan.
- **STOCK** — Sebagai admin, saya ingin stok bahan berkurang otomatis per penjualan dan diberi peringatan saat di bawah batas minimum.
- **REPORT** — Sebagai pemilik, saya ingin mengunduh laporan harian ke Excel untuk pembukuan.
- **WAGE** — Sebagai pemilik, saya ingin upah tiap kasir terhitung otomatis dari shift + aktivitas + bonus penjualan agar saya tak perlu menghitung manual tiap gajian.

## Kebutuhan non-fungsional
- Layar kasir dirender < 1 detik; tambah item ke keranjang < 200 ms (tanpa reload halaman).
- Bisa dipakai di tablet ≥ 768 px dan desktop 1280 px ke atas (konten melebar sampai 1760 px di layar lebar, tidak menyisakan area kosong); target sentuh ≥ 44 px (`docs/26_UI_CONVENTIONS.md`).
- Satu outlet, maksimal ±3 kasir bersamaan.
- Zona waktu aplikasi `Asia/Makassar`; mata uang Rupiah tanpa desimal.

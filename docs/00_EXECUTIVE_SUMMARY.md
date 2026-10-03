# 00 — EXECUTIVE_SUMMARY

## Masalah
Outlet makanan & minuman mencatat penjualan secara manual atau dengan aplikasi yang tidak memotong stok bahan baku. Akibatnya antrean kasir lambat, kas akhir shift sering selisih, dan bahan habis tanpa peringatan.

## Solusi
MyKasir adalah aplikasi kasir berbasis web untuk **satu outlet**. Kasir mencatat pesanan di layar sentuh, menerima Tunai/QRIS statis/Debit-Transfer, lalu mencetak struk. Setiap penjualan otomatis memotong stok bahan sesuai resep. Shift ditutup dengan rekonsiliasi kas, dan pemilik melihat laporan harian.

## Metrik sukses
Transaksi biasa (3 item, tunai) selesai **≤ 30 detik**, dari pilih menu sampai struk tercetak.

## Pengguna sasaran
Pemilik outlet, admin/pengelola stok, dan kasir (detail peran: `docs/05_USER_ROLE.md`).

## Status & cakupan
Greenfield. MVP = fase F1–F5 di `docs/03_ROADMAP.md`. Mode offline (PWA) dijadwalkan setelah MVP. Batas cakupan: `docs/02_SCOPE.md`.

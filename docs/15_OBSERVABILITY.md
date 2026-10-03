# 15 — OBSERVABILITY

## Log aplikasi
- Channel `daily` di `storage/logs/laravel-YYYY-MM-DD.log`, retensi 30 hari.
- Format: pesan + konteks array `['user_id','shift_id','order_id','ref']`.
- Level: `error` = gagal sistem; `warning` = otorisasi ditolak, PIN terkunci, selisih kas ≠ 0; `info` = shift buka/tutup, void, stok masuk/opname.
- JANGAN mencatat password, PIN, `pin_hash`, isi `.env`.

## Jejak audit (bisnis)
Aksi sensitif dicatat di tabel `audit_logs` (daftar `action`: `docs/07_DATA_MODEL.md`) — terpisah dari log teknis, dapat dilihat owner dari halaman Pengaturan.

## Monitoring
- [ASUMSI] Cek kesehatan rute `/up` (bawaan Laravel 12) untuk uptime monitor eksternal.
- [TERBUKA] Metrik performa & alert — ditunda setelah MVP.

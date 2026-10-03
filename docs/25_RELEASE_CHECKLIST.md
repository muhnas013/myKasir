# 25 — RELEASE_CHECKLIST

Jalankan berurutan; berhenti pada langkah pertama yang gagal. Perintah: `docs/11_COMMANDS.md`. Gerbang & rollback: `docs/22_CHANGE_POLICY.md`.

| # | Langkah | Lulus bila |
|---|---|---|
| 1 | Rilis di luar jam ramai outlet (disarankan sebelum buka atau setelah tutup) dan semua shift sudah `closed` | `SELECT COUNT(*) FROM shifts WHERE status='open'` = 0 |
| 2 | `php artisan test` + `./vendor/bin/pint --test` di branch rilis | 0 failures, 0 pelanggaran |
| 3 | `php artisan migrate:status` di produksi → baca migrasi baru; destruktif? → konfirmasi manusia | daftar migrasi baru disetujui |
| 4 | Backup DB (perintah Backup DB) + salin keluar server | file `.sql` > 0 byte dan bisa dibaca `head` |
| 5 | Deploy (perintah Deploy) | exit 0, tanpa error migrasi |
| 6 | Smoke test produksi: login owner, login PIN kasir, buka shift, transaksi tunai 1 item, cetak struk, void dengan PIN, tutup shift, buka laporan hari ini | semua langkah sukses; laporan menampilkan transaksi uji sebagai void |
| 7 | Cek log | `grep -c "ERROR" storage/logs/laravel-$(date +%F).log` tidak bertambah setelah smoke test |
| 8 | Catat versi rilis (tag git `vX.Y.Z`) | tag ter-push |

## Rollback (bila langkah 5–7 gagal)
1. Aktifkan mode perawatan `php artisan down`.
2. `git checkout` tag rilis sebelumnya → deploy ulang.
3. Bila migrasi sudah jalan dan `down()` tidak aman → pulihkan backup langkah 4.
4. `php artisan up`, ulangi smoke test, laporkan ke owner.

# 11 — COMMANDS

Jalankan dari root proyek.

| Tujuan | Perintah |
|---|---|
| Setup: dependensi PHP | `composer install` |
| Setup: dependensi JS | `npm ci` |
| Setup: kunci aplikasi | `php artisan key:generate` |
| Setup: link storage | `php artisan storage:link` |
| Jalankan lokal (server + Vite) | `composer run dev` (atau `php artisan serve` + `npm run dev`) |
| Tes semua | `php artisan test` |
| Tes satu fitur | `php artisan test --filter=CompleteOrderTest` |
| Tes paralel | `php artisan test --parallel` |
| Format kode (cek) | `./vendor/bin/pint --test` |
| Format kode (perbaiki) | `./vendor/bin/pint` |
| Migrasi ⚠️ | `php artisan migrate` |
| Status migrasi | `php artisan migrate:status` |
| Migrasi ulang + data demo (LOKAL SAJA ⚠️ menghapus data) | `php artisan migrate:fresh --seed` |
| Build aset produksi | `npm run build` |
| Cache produksi | `php artisan optimize` |
| Bersihkan cache | `php artisan optimize:clear` |
| Lihat log | `tail -f storage/logs/laravel.log` |
| Backup DB (produksi) ⚠️ | `mysqldump --single-transaction -u $DB_USERNAME -p $DB_DATABASE > backup-$(date +%F-%H%M).sql` |
| Deploy (produksi) ⚠️ | `git pull && composer install --no-dev -o && npm ci && npm run build && php artisan migrate --force && php artisan optimize && sudo systemctl reload php8.3-fpm` |
| Validasi blueprint | `bash scripts/validate.sh` |
| Tutup paksa shift terbuka jam 22:00 (manual/uji) | `php artisan shifts:auto-close` |
| Jalankan scheduler lokal (dev, tanpa cron sistem) | `php artisan schedule:work` |
| Cron scheduler (produksi, 1 baris crontab) ⚠️ | `* * * * * cd /path/ke/mykasir && php artisan schedule:run >> /dev/null 2>&1` |

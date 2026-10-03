# 10 — DEV_ENV

## Prasyarat
PHP 8.3 (ext: pdo_mysql, mbstring, intl, gd, zip, bcmath), Composer 2, Node 20 LTS, MySQL 8. Versi: `docs/09_STACK.md`.

## Setup lokal (urutan)
1. Clone repo, salin `.env.example` → `.env`.
2. Buat database `mykasir` dan `mykasir_test`.
3. Instal dependensi, buat kunci aplikasi, migrasi + seeder demo, build aset, lalu jalankan — perintahnya: `docs/11_COMMANDS.md` (baris Setup).
4. Login demo dari seeder: owner `owner@mykasir.test` / `password`; kasir "Kasir 1" PIN `123456` (hanya `APP_ENV=local`).

## Variabel `.env`
| Nama | Keterangan |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | standar Laravel; `APP_DEBUG=false` di produksi |
| `APP_TIMEZONE` | `Asia/Makassar` |
| `APP_LOCALE` | `id` |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL |
| `SESSION_DRIVER` | `database` |
| `SESSION_LIFETIME` | `720` (12 jam, satu hari kerja) |
| `LOG_CHANNEL`, `LOG_LEVEL` | lihat 15 |
| `FILESYSTEM_DISK` | `public` (logo, QRIS, foto produk) |
| `PIN_MAX_ATTEMPTS`, `PIN_LOCK_MINUTES` | default 5 / 15 |

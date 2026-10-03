# CLAUDE.md

File ini selalu aktif. Untuk hal di luar ini → buka INDEX.md.

## Proyek
MyKasir — aplikasi kasir web satu outlet F&B (transaksi, struk, shift, stok bahan, laporan). Detail: docs/00_EXECUTIVE_SUMMARY.md. Scope: docs/02_SCOPE.md.

## Prinsip kerja agen (non-negotiable)
1. Friksi sebanding irreversibilitas.
2. Lapisan deterministik (validasi, constraint, tes) di bawah penalaran.
3. Human-in-the-loop untuk high-stakes (migrasi DB, hapus data, keamanan, rilis).
4. Jangan refactor keputusan yang disengaja diam-diam.
5. Hormati scope (docs/02). Out-of-scope → berhenti & tanya.

## Stack  (sumber: docs/09)
PHP 8.3 · Laravel 12 · Livewire 3 + Alpine · MySQL 8 · Vite · CSS token kustom — Terlarang: jQuery, Bootstrap/Tailwind, SPA, float untuk uang, PWA sebelum F6

## Struktur & konvensi  (sumber: docs/12)
- Controller/Livewire tipis → `app/Actions/*` (satu use case, dalam `DB::transaction`).
- Uang hanya di `PriceCalculator` (integer Rupiah); stok hanya lewat `StockService`.
- Transaksi/kas/stok/audit tidak pernah di-hard-delete.

## Perintah penting  (sumber: docs/11)
`composer run dev` · `php artisan test` · `./vendor/bin/pint --test` · `php artisan migrate` ⚠️

## Guardrail inti  (penuh: docs/20, 21, 22)
- JANGAN hard-code rahasia; semua di `.env`.
- JANGAN lewati `authorize()`; sembunyi tombol ≠ otorisasi.
- JANGAN terima harga/total dari klien; server menghitung ulang.
- JANGAN tambah dependency atau migrasi destruktif tanpa konfirmasi.
- JANGAN commit ke `main`; branch per fitur.
- JANGAN longgarkan tes agar hijau.

## Antarmuka  (sumber: docs/26_UI_CONVENTIONS.md)
Jangkar: rancangan kanvas MyKasir (hijau `#17603F`, Plus Jakarta Sans). Larangan: nilai di luar token, gradien/emoji, halaman tanpa empat state.

## Alur per-task  (penuh: docs/17, 19)
Baca INDEX.md → muat dokumen relevan → konfirmasi scope → vertical slice → tes (docs/13) → DoD (docs/24).

## Definisi selesai (ringkas; penuh: docs/24)
Kriteria 23 lulus + `php artisan test` & pint hijau + guardrail & token UI dipatuhi.

## Untuk apa pun di luar ini → INDEX.md

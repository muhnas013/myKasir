# 09 — STACK

| Lapisan | Teknologi | Versi |
|---|---|---|
| Runtime | PHP | 8.3 |
| Framework | Laravel | 12.x |
| UI reaktif | Livewire | 3.x |
| JS ringan | Alpine.js (bawaan Livewire) | 3.x |
| Build aset | Vite + `laravel-vite-plugin` | 6.x |
| Node (build saja) | Node.js | 20 LTS |
| CSS | CSS kustom berbasis custom properties dari token 26 (`resources/css/app.css`) | — |
| Ikon | Lucide (SVG inline via komponen `<x-icon>`) | 0.4xx |
| Font | Plus Jakarta Sans (Google Fonts, di-self-host di `public/fonts`) | — |
| Basis data | MySQL | 8.0 |
| Export | `maatwebsite/excel` | 3.1 |
| Tes | PHPUnit (via `php artisan test`) + Livewire testing | 11.x |
| Kualitas kode | Laravel Pint | 1.x |
| Server | Ubuntu 24.04, Nginx, PHP-FPM 8.3 | — |
| Offline (F6 saja) | Service Worker (cache app-shell, vanilla JS) + IndexedDB (antrean transaksi, via Alpine) | API browser bawaan, tanpa pustaka tambahan |

## Terlarang
| Teknologi | Alasan |
|---|---|
| jQuery | Tumpang tindih dengan Alpine/Livewire |
| Bootstrap, Tailwind, pustaka UI lain | Token 26 sudah final; framework CSS mengundang nilai di luar token |
| React, Vue, SPA terpisah | Arsitektur monolit (08) |
| `float`/`double`/`decimal` untuk uang | Pembulatan tak deterministik; uang = integer Rupiah (07) |
| Paket payment gateway / QRIS dinamis | Out-of-scope (02) |
| Service worker / PWA sebelum F6 | Ditunda ke F6 (02) |
| Background Sync API, PouchDB/RxDB, pustaka sync pihak ketiga lain | F6 pakai antrean manual (IndexedDB + event online/offline), bukan Background Sync — dukungan browser tidak konsisten (lih. 02 asumsi F6) |
Menambah dependency apa pun: lewat `docs/22_CHANGE_POLICY.md`.

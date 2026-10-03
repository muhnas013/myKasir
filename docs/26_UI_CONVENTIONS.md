# 26 — UI_CONVENTIONS

## Jangkar desain
Rancangan kanvas "Rancangan Aplikasi Kasir Outlet" (4 artboard: Kasir, Menu & Stok, Laporan, Pengaturan). Bila kanvas ≠ dokumen ini, **dokumen ini menang**.

## Token (nilai di luar tabel ini TERLARANG; didefinisikan sekali di `resources/css/app.css` `:root`)
| Kategori | Token CSS | Nilai |
|---|---|---|
| Warna – primer | `--c-primary` / `--c-primary-hover` / `--c-primary-soft` | `#17603F` / `#0F4A30` / `#E4F0E8` |
| Warna – latar | `--c-bg` / `--c-surface` / `--c-surface-alt` / `--c-subtle` | `#F5F4F0` / `#FFFFFF` / `#FCFBF9` / `#F2F0EB` |
| Warna – garis | `--c-border` / `--c-border-soft` / `--c-border-strong` | `#E6E3DC` / `#EFEDE8` / `#D9D5CC` |
| Warna – teks | `--c-text` / `--c-text-label` / `--c-text-muted` / `--c-on-primary` | `#1B1A17` / `#4A4741` / `#6B6760` / `#FFFFFF` |
| Warna – nonaktif | `--c-disabled` | `#A9A398` |
| Semantik – sukses | `--c-success` / `--c-success-soft` | `#17603F` / `#E4F0E8` |
| Semantik – peringatan | `--c-warning` / `--c-warning-soft` / `--c-warning-border` | `#8A3F06` / `#FDF0E1` / `#F3D3AC` |
| Semantik – bahaya | `--c-danger` / `--c-danger-soft` | `#9F1F14` / `#FBE9E7` |
| Semantik – info [ASUMSI] | `--c-info` / `--c-info-soft` | `#1D4ED8` / `#E6EEFC` |
| Tile kategori | `--c-cat-1..4` | `#EFE6DA` (Kopi) · `#E2EEE5` (Non-Kopi) · `#F6E5D3` (Makanan) · `#F2EDD5` (Camilan); kategori ke-5+ berulang dari 1 |
| Grafik | `--c-chart-1..3` | `#17603F` · `#6FAE8B` · `#C9E0D2` (sorotan/series utama selalu 1) |
| Overlay dialog | `--c-overlay` | `rgba(27,26,23,0.5)` |
| Huruf | `--font-sans` | `'Plus Jakarta Sans', system-ui, sans-serif`; angka: `font-variant-numeric: tabular-nums` |
| Skala huruf | `--fs-xs/sm/base/md/lg/xl/2xl` | 12 / 13 / 14 / 16 / 18 / 22 / 26 px (total besar 30 px boleh `--fs-3xl`) |
| Tebal | — | 500 isi, 600 label/tombol, 700 judul, 800 angka total |
| Spasi | `--sp-1..8` | 4 / 8 / 12 / 16 / 20 / 24 / 28 / 32 px |
| Radius | `--r-sm` / `--r-md` / `--r-lg` / `--r-xl` / `--r-pill` | 6 (badge) / 10 (kontrol) / 14 (kartu) / 18 (dialog) / 999 |
| Bayangan | `--shadow-dialog` / `--shadow-seg` | `0 24px 60px rgba(0,0,0,.25)` / `0 1px 3px rgba(0,0,0,.12)`; kartu TANPA bayangan |
| Ukuran sentuh | `--h-control` / `--h-primary` | 44 px minimum / 52 px (tombol Bayar) |
| Lebar | sidebar 220 px; panel pesanan 360 px; breakpoint `768px`, `1280px` | |

## Inventaris komponen (`resources/views/components/`)
| Komponen | Varian | Catatan |
|---|---|---|
| `x-button` | primary, secondary (garis), ghost (putus-putus), danger | tinggi ≥ 44; `wire:loading.attr="disabled"` wajib di aksi simpan/bayar |
| `x-input`, `x-select`, `x-textarea` | default, error | label selalu terlihat di atas field |
| `x-toggle` | on/off | `<button role="switch" aria-checked>` |
| `x-badge` | neutral, success, warning, danger | |
| `x-modal` | default, dialog PIN | fokus terkunci di dalam, Esc menutup (kecuali saat memproses) |
| `x-toast` | success, warning, danger | pojok kanan atas, 4 detik |
| `x-table` | default | dibungkus `overflow-x:auto` |
| `x-empty-state` | default | ikon + judul + 1 kalimat + aksi |
| `x-icon` | Lucide, stroke 1.8 | satu set ikon saja |
| `x-segmented` | — | jenis pesanan, periode laporan |

## Halaman → pola
| Halaman | Rute | Pola | Navigasi |
|---|---|---|---|
| Login | `/login`, `/login/pin` | autentikasi | tanpa sidebar |
| Buka/Tutup Shift | `/shift/open`, `/shift/close` | formulir | sidebar |
| Kasir | `/pos` | dasbor transaksi (grid menu + panel pesanan + dialog bayar) | sidebar |
| Menu & Stok | `/menu`, `/stock` | daftar+filter dengan panel formulir samping | sidebar |
| Laporan | `/reports` | dasbor | sidebar |
| Pengaturan | `/settings` | formulir berkelompok (kartu) | sidebar |
| Struk | `/receipts/{order}` | cetak 58 mm | tanpa sidebar |
Sidebar: Kasir · Menu & Stok · Laporan · Pengaturan + kartu shift aktif; item disembunyikan sesuai peran (05). Di < 768 px sidebar menjadi bar atas.

## Empat state wajib (setiap halaman/komponen ber-data)
| State | Aturan |
|---|---|
| Kosong | `x-empty-state` dengan aksi (mis. "Belum ada pesanan — ketuk menu di kiri") |
| Memuat | skeleton abu `--c-subtle` seukuran konten; tombol aksi nonaktif |
| Gagal | toast bahaya + konten lama dipertahankan; tombol "Coba lagi" |
| Sukses | toast sukses singkat atau layar sukses (pembayaran) |

## Bahasa & mikroteks
Bahasa Indonesia, nada netral-ramah, kalimat aktif. Rupiah `Rp 18.000` (titik ribuan, tanpa desimal). Tanggal `Sabtu, 3 Oktober 2026`; jam `14.52` (WITA). Kata kerja tombol baku: **Simpan**, **Batal**, **Ubah**, **Tambah**, **Hapus**, **Bayar**, **Cetak Struk**, **Pesanan Baru**, **Buka Shift**, **Tutup Shift**.

## Larangan UI
- Nilai warna/ukuran/radius/bayangan di luar tabel token; hex di Blade/inline style.
- Gradien, glassmorphism, bayangan pada kartu, animasi dekoratif.
- Emoji di antarmuka; ikon selain Lucide.
- Pustaka UI/CSS baru (lihat 09).
- Teks placeholder (`Lorem`, `[ISI]`) tersisa di halaman.
- `<div onclick>` — elemen interaktif wajib `<button>`/`<a>`; tombol ikon wajib `aria-label`.
- Kontras teks < 4.5:1.
Verifikasi UI: `docs/23_ACCEPTANCE_CRITERIA.md`.

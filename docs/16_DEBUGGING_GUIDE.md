# 16 — DEBUGGING_GUIDE

Lokasi log: `docs/15_OBSERVABILITY.md`. Perintah: `docs/11_COMMANDS.md`.

## Gejala → diagnosa
| Gejala | Periksa |
|---|---|
| Total di layar ≠ total di struk/DB | Ada perhitungan uang di luar `PriceCalculator` (grep `* 0.1`, `round(` di Livewire/Blade) |
| Stok bahan tidak berkurang | Produk punya `track_stock=true` sekaligus resep? (hanya salah satu); resep opsi varian terlewat; `StockService` dipanggil di luar transaksi |
| Order ganda saat tombol diklik dua kali | `idempotency_key` tidak dikirim/di-reset per keranjang; tombol tidak `wire:loading.attr="disabled"` |
| Nomor order bentrok `A-0001` | `OrderNumberGenerator` tanpa `lockForUpdate`; `business_date` memakai UTC, bukan Asia/Makassar |
| Laporan "kemarin" berisi transaksi lewat tengah malam | Filter memakai `created_at` alih-alih `business_date` |
| Struk terpotong/terlalu kecil | Skala print browser ≠ 100% atau margin tidak `0`; CSS `@page { size: 58mm auto; margin: 0 }` |
| Livewire state hilang setelah aksi | Properti publik bertipe objek Eloquent besar — simpan id/array saja |
| 419 Page Expired di kasir | Sesi habis (`SESSION_LIFETIME`); layar kasir harus menangani dengan meminta PIN ulang, bukan kehilangan keranjang |

## Jebakan proyek (greenfield — dicegah sejak awal)
- **Uang float**: `18000 * 1.1` = `19800.000000000004`. Selalu integer + `intdiv`/`round` di `PriceCalculator`.
- **`avg_cost` per 1000 satuan**: lupa membagi 1000 saat menghitung HPP → HPP 1000× lipat.
- **Shift ganda**: dua tab membuka shift bersamaan → validasi di Action dengan `lockForUpdate` pada user, bukan hanya di UI.

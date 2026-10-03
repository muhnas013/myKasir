# 13 — TESTING

## Jenis tes
| Jenis | Lokasi | Cakupan target |
|---|---|---|
| Unit | `tests/Unit/` | 100% cabang `PriceCalculator`, `Money`, rumus `avg_cost`, `OrderNumberGenerator` |
| Feature (HTTP + Livewire) | `tests/Feature/` | Setiap Action + setiap ability Policy (diizinkan & ditolak) |
| Manual (smoke) | checklist 25 | Cetak struk 58 mm di printer nyata, layar tablet 768 px |

DB tes: MySQL `mykasir_test` dengan trait `RefreshDatabase` (bukan SQLite — perilaku `lockForUpdate`/enum harus sama dengan produksi).

## Wajib dites (alur kritikal)
1. `CompleteOrder`: total sesuai aturan hitung 06 (pajak, layanan, diskon, pembulatan); order + item + payment + stock_movements tercipta dalam satu transaksi; rollback penuh bila stok kurang; idempotency key ganda → satu order.
2. `VoidOrder`: stok kembali; `expected_cash` shift berkurang; PIN salah ditolak; kunci setelah 5x salah.
3. `CloseShift`: `expected_cash` = modal + tunai masuk − kembalian − tunai void; selisih ≠ 0 tanpa catatan ditolak; pesanan `open` memblokir tutup.
4. `ReceiveStock`: rumus `avg_cost` (06 P6).
5. Policy: tiap baris matriks 05 punya tes ✓ dan ✗ (kasir tidak bisa membuka `/menu`, `/settings`, laporan semua shift → 403).
6. Laporan: angka omzet = Σ `orders.total` berstatus `paid` (void tidak dihitung) untuk `business_date`.

## Aturan
- Tes ditulis bersama kode di slice yang sama, bukan menyusul.
- Factory untuk tiap model di `database/factories/`.
- Dilarang menghapus/melonggarkan assertion agar hijau (lihat 18).

Perintah: `docs/11_COMMANDS.md`.

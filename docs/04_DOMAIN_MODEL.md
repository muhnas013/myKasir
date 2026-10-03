# 04 — DOMAIN_MODEL

## Glosarium
| Istilah (UI) | Identifier kode | Makna |
|---|---|---|
| Outlet | `settings` | Satu-satunya usaha yang dilayani aplikasi |
| Shift | `Shift` | Sesi kerja satu kasir dari buka sampai tutup; semua pesanan terikat ke satu shift |
| Modal awal | `opening_cash` | Uang tunai di laci saat shift dibuka |
| Kas seharusnya | `expected_cash` | Modal awal + penerimaan tunai − kembalian, dihitung sistem |
| Selisih kas | `cash_difference` | Kas fisik dihitung − kas seharusnya |
| Pesanan / transaksi | `Order` | Satu tagihan pelanggan; nomor `A-0001` berurutan per hari |
| Item pesanan | `OrderItem` | Satu baris produk + opsi varian + qty; harga dibekukan saat dibuat (snapshot) |
| Varian | `VariantGroup` / `VariantOption` | Grup pilihan (Ukuran, Suhu) dan opsinya (Large +Rp 5.000) |
| Bahan baku | `Ingredient` | Barang yang stoknya dilacak dalam satuan dasar (g, ml, pcs) |
| Resep | `Recipe` | Jumlah bahan yang dipakai per 1 porsi produk atau per opsi varian |
| HPP | `cost` | Harga pokok per porsi = Σ (qty resep × `avg_cost` bahan) |
| Pergerakan stok | `StockMovement` | Catatan setiap perubahan stok (jual, void, masuk, opname); stok tak pernah diubah tanpa catatan ini |
| PB1 | `tax` | Pajak restoran daerah, persen dari subtotal setelah diskon |
| Void | `status=void` | Pembatalan transaksi lunas; data tetap disimpan |
| Open bill | `status=open` | Pesanan disimpan, belum dibayar |

## Entitas & relasi konseptual
- **User** membuka banyak **Shift**; satu user hanya boleh punya satu shift berstatus `open`.
- **Shift** memuat banyak **Order**; **Order** memuat banyak **OrderItem** dan **Payment**.
- **Category** memuat banyak **Product**; **Product** punya banyak **VariantGroup** → **VariantOption**.
- **Product** dan **VariantOption** punya **Recipe** ke banyak **Ingredient**.
- **Ingredient** dan **Product** (bila `track_stock`) punya riwayat **StockMovement**.
- **AuditLog** mencatat aksi sensitif dari semua entitas.

Skema fisik: `docs/07_DATA_MODEL.md`.

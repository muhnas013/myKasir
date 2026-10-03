# 02 — SCOPE

## In-scope
- Semua fitur berprioritas MVP di `docs/01_PRD.md` (AUTH, SET, MENU, POS, PAY, SHIFT, VOID, STOCK, REPORT).
- Satu outlet, satu basis data, aplikasi online yang dihosting di VPS.
- Struk dicetak lewat dialog print browser ke printer thermal 58 mm.

## Out-of-scope (JANGAN dibangun tanpa perubahan scope tertulis)
- **Multi-outlet/cabang**: tidak ada kolom `outlet_id`, tidak ada pemilihan cabang.
- **QRIS dinamis / payment gateway** (Midtrans, Xendit, dll.): QRIS hanya gambar statis + konfirmasi manual kasir.
- **Mode offline / service worker / sinkronisasi**: ditunda ke F6. Jangan menambah PWA di MVP.
- **Aplikasi mobile native** (Android/iOS).
- **Integrasi ojek online** (GoFood, GrabFood, ShopeeFood).
- **Akuntansi** (jurnal, neraca) dan e-filing pajak.
- **Member, poin, voucher, loyalti.**
- **Kitchen display / cetak tiket dapur.**
- **Multi-bahasa**: UI hanya Bahasa Indonesia.
- **Cetak langsung tanpa dialog** (QZ Tray, RawBT, WebUSB/Bluetooth).

## Asumsi & batasan
- [ASUMSI] Satu jenis pajak aktif (PB1) + satu biaya layanan opsional.
- [ASUMSI] Diskon hanya per transaksi (nominal atau persen), bukan per item.
- [ASUMSI] "Simpan" (open bill) hanya menahan pesanan berstatus `open` di shift yang sama; tidak ada pemisahan tagihan (split bill).

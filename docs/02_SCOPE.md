# 02 — SCOPE

## In-scope
- Semua fitur berprioritas MVP di `docs/01_PRD.md` (AUTH, SET, MENU, POS, PAY, SHIFT, VOID, STOCK, REPORT).
- Satu outlet, satu basis data, aplikasi online yang dihosting di VPS.
- Struk dicetak lewat dialog print browser ke printer thermal 58 mm.

## Out-of-scope (JANGAN dibangun tanpa perubahan scope tertulis)
- **Multi-outlet/cabang**: tidak ada kolom `outlet_id`, tidak ada pemilihan cabang.
- **QRIS dinamis / payment gateway** (Midtrans, Xendit, dll.): QRIS hanya gambar statis + konfirmasi manual kasir.
- **Mode offline / service worker / sinkronisasi di MVP (F1-F5)**: jangan menambah PWA sebelum F6. Lingkup F6 sendiri: lihat bagian "F6 — asumsi mode offline" di bawah.
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

## F6 — asumsi mode offline
- [ASUMSI] Pola: antrean manual di IndexedDB (bukan Background Sync API) + Service Worker untuk cache app-shell saja. Alasan: Background Sync API tidak didukung Safari/iOS dan tidak konsisten lintas browser; device kasir tetap (bukan kebutuhan lintas-platform) sehingga kontrol penuh di kode sendiri lebih aman. Tidak ada pustaka sync pihak ketiga (PouchDB/RxDB dll.) — lih. `docs/09_STACK.md`.
- [ASUMSI] Hanya layar transaksi kasir (POS/PAY) yang bekerja offline, ditulis dengan Alpine/vanilla JS. Layar lain (laporan, stok, pengaturan) tetap online-only dan boleh tidak dapat diakses saat offline.
- [ASUMSI] Cakupan F6 iterasi pertama: hanya produk tanpa grup varian sama sekali (wajib maupun opsional — opsional pun butuh harga tambahan yang sebaiknya tak ditebak JS), tanpa diskon, metode Tunai saja. Varian, diskon, dan QRIS/Debit offline ditunda ke iterasi berikutnya (di luar slice ini) karena masing-masing butuh verifikasi/konfirmasi yang bergantung koneksi.
- [ASUMSI] Dedup order memakai `orders.idempotency_key` yang sudah ada (07) — dibuat di klien saat keranjang dimulai, dipakai ulang persis saat retry sinkronisasi; tidak perlu kolom baru.
- [KOREKSI setelah implementasi — stok TIDAK pernah negatif]: `StockService`/`CompleteOrder` yang sudah ada menolak (rollback, 422) sinkron yang stoknya tak cukup di titik sinkron, sama seperti alur online — stok tidak pernah dibiarkan negatif. Risiko sebenarnya: order offline kedua yang stoknya keburu habis (disinkron duluan oleh order lain) GAGAL permanen dan tersangkut di antrean lokal device itu — kasir sudah terima tunai tapi order tidak pernah tercatat di sistem sampai ditinjau manual pemilik. Lih. `docs/06_BUSINESS_PROCESS.md` P7, `docs/16_DEBUGGING_GUIDE.md`.
- [ASUMSI] Total yang tercetak di struk offline adalah estimasi klien dari tarif pajak/biaya/pembulatan yang di-cache saat login terakhir. Server tetap satu-satunya penghitung otoritatif (21) saat sinkronisasi; bila hasil server ≠ estimasi klien, order ditandai `offline_adjusted` di audit log untuk ditinjau admin — tidak pernah ditimpa diam-diam.

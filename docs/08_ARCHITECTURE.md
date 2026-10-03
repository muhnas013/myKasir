# 08 — ARCHITECTURE

## Lapisan
```
Browser (Blade + Livewire 3 + Alpine.js)
   │  wire:click / form submit
Livewire Component / Controller   ← tipis: validasi (FormRequest/rules), otorisasi (Policy), panggil Action
   │
app/Actions/*        ← satu use case = satu kelas (CompleteOrder, VoidOrder, CloseShift, ReceiveStock)
app/Services/*       ← logika bersama (PriceCalculator, StockService, SettingService, ReportService)
   │
Eloquent Models (app/Models)  →  MySQL 8
```

| Lapisan | Tanggung jawab | Dilarang |
|---|---|---|
| Livewire/Controller | state UI, validasi input, `authorize()`, memanggil satu Action | query bisnis kompleks, menghitung uang |
| Action | satu use case dalam `DB::transaction()`, menulis audit log | membaca request/session langsung |
| Service | kalkulasi murni & query bersama | efek samping di luar tanggung jawabnya |
| Model | relasi, cast, scope | logika bisnis lintas model |

## Keputusan arsitektural kunci
| Keputusan | Alasan |
|---|---|
| Monolit Laravel + Livewire (bukan SPA) | Satu deployable, tim kecil, layar kasir interaktif tanpa API terpisah |
| Semua penulisan transaksi lewat `App\Actions\Orders\CompleteOrder` dalam `DB::transaction()` + `lockForUpdate()` pada baris stok | Mencegah stok/kas tidak konsisten saat 2–3 kasir bersamaan |
| `App\Services\PriceCalculator` satu-satunya tempat aturan hitung (06) | Total di layar, struk, dan DB dijamin sama |
| Harga & HPP di-snapshot ke `order_items` | Laporan lama tidak berubah saat harga menu diubah |
| Struk = view Blade `receipts.show` + CSS `@media print` 58 mm | Tanpa driver/aplikasi tambahan (scope 02) |
| Export Excel lewat `maatwebsite/excel` di job sinkron | Volume satu outlet kecil; queue belum diperlukan |
| Livewire hanya untuk layar interaktif (POS, Menu, Laporan); halaman lain Blade biasa | Mengurangi kompleksitas state |

## Integrasi eksternal
Tidak ada di MVP. QRIS statis = gambar yang diunggah owner (`settings.qris_image_path`).

## Persiapan F6 (offline)
Jangan bangun sekarang. Yang sudah disiapkan: `orders.idempotency_key` (UUID dari klien) agar sinkronisasi nanti tidak membuat order ganda.

Stack & versi: `docs/09_STACK.md`.

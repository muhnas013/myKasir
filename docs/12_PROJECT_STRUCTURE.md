# 12 — PROJECT_STRUCTURE

## Pohon folder (yang penting)
```
app/
  Actions/{Auth,Orders,Shifts,Stock,Menu,Settings}/   # satu use case per kelas, method handle()
  Services/        # PriceCalculator, StockService, SettingService, ReportService, OrderNumberGenerator
  Livewire/{Pos,Menu,Stock,Reports,Settings}/          # komponen interaktif
  Http/Controllers/  # halaman non-Livewire (auth, struk, export)
  Http/Requests/     # FormRequest untuk controller
  Policies/          # satu Policy per model + Gate di AppServiceProvider (ability 05)
  Models/
  Enums/             # Role, OrderStatus, OrderType, PaymentMethod, StockMovementType
  Exports/           # kelas maatwebsite/excel
  Support/Money.php  # format & pembulatan Rupiah
database/migrations, database/seeders (DemoSeeder hanya untuk local)
resources/
  css/app.css        # SATU-SATUNYA tempat token CSS (:root) dari 26
  views/components/  # komponen Blade: x-button, x-input, x-badge, x-modal, x-toggle, x-table, x-empty-state, x-icon
  views/layouts/app.blade.php   # sidebar + konten
  views/livewire/...            # view komponen Livewire, cermin app/Livewire
  views/receipts/show.blade.php # struk cetak
  js/offline/        # F6: queue.js (tulis/baca antrean IndexedDB), sync.js (replay ke POST /pos/offline-sync), pricer.js (estimasi PriceCalculator), pos-component.js (Alpine) — dibundel Vite ke app.js, bukan file terpisah
public/
  sw.js              # F6: Service Worker — cache app-shell saja (bukan Background Sync); plain JS, TIDAK lewat Vite, scope root agar bisa kontrol seluruh origin
  manifest.webmanifest  # F6: metadata install PWA
routes/web.php       # semua rute; dikelompokkan per middleware peran
tests/Feature/{Auth,Pos,Shift,Stock,Reports,Settings,Offline}/, tests/Unit/
```

## Konvensi penamaan
| Jenis | Konvensi | Contoh |
|---|---|---|
| Model | PascalCase tunggal | `OrderItem` |
| Tabel/kolom | snake_case jamak / snake_case | `order_items.unit_price` |
| Action | Kata kerja + objek | `CompleteOrder`, `VoidOrder`, `CloseShift` |
| Livewire | Domain\Halaman | `Pos\Register`, `Menu\ProductTable` |
| Rute | kebab, nama bertitik | `/menu/products` → `menu.products.index` |
| Enum | PascalCase, case = nilai DB | `OrderStatus::Paid` = `'paid'` |
| Tes | `{Action/Fitur}Test` | `CompleteOrderTest` |

## Lokasi kode
- Uang dihitung HANYA di `App\Services\PriceCalculator`; format tampilan HANYA lewat `App\Support\Money::format()` (`Rp 18.000`).
- Perubahan stok HANYA lewat `App\Services\StockService` (selalu menulis `stock_movements`).
- Teks UI ditulis langsung di Blade (Bahasa Indonesia); pesan validasi di `lang/id/validation.php`.

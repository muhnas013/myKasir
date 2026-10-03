# 07 — DATA_MODEL

Sumber kebenaran skema. MySQL 8, InnoDB, `utf8mb4_unicode_ci`. Semua tabel memakai `id` BIGINT UNSIGNED PK + `created_at`/`updated_at` kecuali disebut lain.
**Konvensi uang:** `BIGINT UNSIGNED` Rupiah utuh (tanpa desimal) — nama kolom tanpa akhiran. **Kuantitas bahan:** `DECIMAL(12,3)` dalam satuan dasar bahan.

## users
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| name | varchar(100) | not null | |
| email | varchar(150) | unique, nullable | wajib untuk owner/admin |
| password | varchar(255) | nullable | bcrypt; wajib untuk owner/admin |
| pin_hash | varchar(255) | nullable | bcrypt PIN 6 digit; wajib untuk cashier & admin |
| role | enum('owner','admin','cashier') | not null | lihat 05 |
| is_active | boolean | default true | tidak pernah dihapus |
| last_login_at | timestamp | nullable | |

## settings
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| key | varchar(64) | unique | mis. `outlet_name`, `outlet_address`, `outlet_phone`, `logo_path`, `qris_image_path`, `tax_enabled`, `tax_rate_bp`, `service_enabled`, `service_rate_bp`, `rounding_unit`, `receipt_footer`, `allow_negative_stock`, `method_cash`, `method_qris`, `method_card` |
| value | text | nullable | di-cast oleh `App\Services\SettingService` |
Persen disimpan dalam basis poin (`tax_rate_bp` 1000 = 10%).

## shifts
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| user_id | fk users | not null | |
| status | enum('open','closed') | not null | satu `open` per user (dicek di Action + indeks) |
| opened_at / closed_at | timestamp | closed_at nullable | |
| opening_cash | bigint unsigned | not null | |
| expected_cash | bigint unsigned | nullable | diisi saat tutup |
| counted_cash | bigint unsigned | nullable | |
| cash_difference | bigint (signed) | nullable | counted − expected |
| closing_note | text | nullable | wajib bila selisih ≠ 0 |
| closed_by | fk users | nullable | berbeda dari user_id bila force-close |

## categories
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| name | varchar(60) | unique | |
| sort_order | smallint unsigned | default 0 | |

## products
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| category_id | fk categories | not null, restrict on delete | |
| sku | varchar(20) | unique | mis. `KP-001` |
| name | varchar(100) | not null | |
| price | bigint unsigned | not null | |
| image_path | varchar(255) | nullable | storage `public/products` |
| is_active | boolean | default true | tampil di kasir |
| track_stock | boolean | default false | hanya untuk produk tanpa resep |
| stock_qty | int | default 0 | dipakai bila track_stock |
| deleted_at | timestamp | nullable | soft delete |

## variant_groups
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| product_id | fk products | cascade | |
| name | varchar(50) | not null | mis. Ukuran |
| is_required | boolean | default false | |
| max_select | tinyint unsigned | default 1 | |

## variant_options
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| variant_group_id | fk variant_groups | cascade | |
| name | varchar(50) | not null | |
| price_delta | bigint unsigned | default 0 | tambahan harga |

## ingredients
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| name | varchar(100) | unique | |
| unit | enum('g','ml','pcs') | not null | satuan dasar |
| stock_qty | decimal(12,3) | not null default 0 | |
| min_qty | decimal(12,3) | default 0 | batas peringatan |
| avg_cost | bigint unsigned | default 0 | Rupiah per 1000 satuan dasar (per kg/L/1000 pcs) |
| deleted_at | timestamp | nullable | |

## recipes
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| ingredient_id | fk ingredients | restrict | |
| product_id | fk products | nullable, cascade | tepat satu dari product_id / variant_option_id terisi |
| variant_option_id | fk variant_options | nullable, cascade | |
| qty | decimal(12,3) | > 0 | per 1 porsi |
Unik: (`ingredient_id`, `product_id`, `variant_option_id`).

## orders
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| number | varchar(12) | unique | `A-0001`, reset per tanggal bisnis |
| business_date | date | index | tanggal Asia/Makassar |
| shift_id | fk shifts | not null | |
| user_id | fk users | not null | kasir pembuat |
| status | enum('open','paid','void') | index | |
| order_type | enum('dine_in','take_away') | | |
| customer_label | varchar(50) | nullable | nama/meja |
| note | text | nullable | |
| subtotal, discount, service, tax, rounding, total | bigint (rounding signed, sisanya unsigned) | not null | aturan hitung: 06 |
| discount_approved_by | fk users | nullable | |
| idempotency_key | char(36) | unique | cegah order ganda |
| paid_at | timestamp | nullable | |
| voided_at / voided_by / void_reason | timestamp / fk users / varchar(255) | nullable | |

## order_items
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| order_id | fk orders | cascade | |
| product_id | fk products | restrict | |
| product_name | varchar(100) | not null | snapshot |
| unit_price | bigint unsigned | not null | snapshot harga + tambahan opsi |
| qty | smallint unsigned | ≥ 1 | |
| line_total | bigint unsigned | | unit_price × qty |
| unit_cost | bigint unsigned | default 0 | snapshot HPP per porsi |
| options | json | nullable | `[{"id":1,"name":"Large","price_delta":5000}]` |
| note | varchar(150) | nullable | |

## payments
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| order_id | fk orders | cascade | MVP: tepat satu per order |
| method | enum('cash','qris','card') | not null | card = Debit/Transfer |
| amount | bigint unsigned | | = orders.total |
| paid_amount | bigint unsigned | | uang diterima (tunai) |
| change_amount | bigint unsigned | default 0 | |
| reference | varchar(50) | nullable | wajib untuk card |

## stock_movements
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| ingredient_id | fk ingredients | nullable | tepat satu dari ingredient_id / product_id |
| product_id | fk products | nullable | untuk track_stock |
| type | enum('sale','void_return','purchase','adjustment') | not null | |
| qty | decimal(12,3) | not null | negatif = keluar |
| total_cost | bigint unsigned | nullable | untuk purchase |
| order_id | fk orders | nullable | |
| user_id | fk users | not null | |
| note | varchar(255) | nullable | wajib untuk adjustment |
Tanpa `updated_at`; baris tidak pernah diubah atau dihapus.

## audit_logs
| Kolom | Tipe | Constraint | Catatan |
|---|---|---|---|
| user_id | fk users | nullable | pelaku |
| approved_by | fk users | nullable | pemberi PIN |
| action | varchar(50) | index | `order.void`, `order.discount`, `product.price_changed`, `stock.adjusted`, `setting.updated`, `user.updated`, `auth.pin_locked` |
| subject_type / subject_id | varchar(50) / bigint | | |
| old_values / new_values | json | nullable | |
| ip | varchar(45) | | |
Tanpa `updated_at`; append-only.

## Indeks penting
- `orders (business_date, status)`, `orders (shift_id)`, `stock_movements (ingredient_id, created_at)`, `audit_logs (action, created_at)`.

## Data sensitif
`password`, `pin_hash` (hash, tidak pernah di-log/diserialisasi — `$hidden` di model). `orders`, `payments`, `stock_movements`, `audit_logs`, `shifts` tidak pernah di-hard-delete. Aturan: `docs/21_SECURITY_RULES.md`.

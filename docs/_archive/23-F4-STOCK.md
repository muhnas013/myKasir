# 23 (arsip) — F4 — STOCK

Diterima: 2026-10-04. Penegakan pindah ke test suite.

- [x] Jual 1 Es Kopi Susu (resep: espresso 18 g, susu 120 ml, gula aren 25 ml) → tiap bahan berkurang sesuai, 3 baris `stock_movements` tipe `sale`. (`StockMovementTest::test_selling_product_with_recipe_deducts_each_ingredient_and_logs_sale_movements`)
- [x] Stok masuk susu 5.000 ml total Rp 90.000 saat stok 2.000 ml @ avg_cost 17.000/1000 ml → `avg_cost` baru = 17.714 per 1000 ml (half-up). (`StockMovementTest::test_stock_in_recalculates_weighted_average_avg_cost`)
- [x] Bahan dengan `stock_qty ≤ min_qty` tampil di banner "hampir habis". (`StockManagementTest::test_low_stock_ingredients_are_listed`, `StockMovementTest::test_ingredients_at_or_below_min_qty_are_low_stock`)
- [x] Opname tanpa alasan → ditolak; dengan alasan → baris `adjustment`. (`StockMovementTest::test_opname_without_reason_is_rejected`, `test_opname_with_reason_records_adjustment`)
- [x] Produk yang bahannya tidak cukup untuk 1 porsi tampil "Habis" di kasir. (`StockMovementTest::test_product_without_enough_recipe_stock_shows_habis_in_grid`)

Tambahan (konsekuensi desain resep, bukan butir AC terpisah):
- [x] Penjualan dibatalkan bila bahan tak cukup (`allow_negative_stock=false`); tidak ada baris `orders`/`stock_movements` baru. (`StockMovementTest::test_insufficient_ingredient_stock_blocks_sale`)
- [x] Void transaksi berresep mengembalikan stok tiap bahan. (`StockMovementTest::test_void_returns_ingredient_stock`)
- [x] HPP per porsi (resep dasar + opsi terpilih) disnapshot ke `order_items.unit_cost` saat pricing. (`RecipeManagementTest::test_porsi_cost_includes_base_and_selected_option`)
- [x] Resep disinkron per `ingredient_id`; baris yang tak dikirim ulang dihapus. (`RecipeManagementTest::test_save_recipe_removes_lines_not_resent`)

```
php artisan test --filter='Stock|Recipe' → 25 passed, 0 failures
php artisan test → 138 passed, 0 failures
```

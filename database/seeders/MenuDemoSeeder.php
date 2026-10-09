<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MenuDemoSeeder extends Seeder
{
    /** Data uji standar (docs/23) — hanya untuk lokal. */
    public function run(): void
    {
        $minuman = Category::firstOrCreate(['name' => 'Minuman'], ['sort_order' => 1]);
        $makanan = Category::firstOrCreate(['name' => 'Makanan'], ['sort_order' => 2]);

        $daftarMinuman = [
            ['sku' => 'MN-001', 'name' => 'Es Teh Manis', 'price' => 5000, 'cost_price' => 1500],
            ['sku' => 'MN-002', 'name' => 'Es Jeruk Peras', 'price' => 8000, 'cost_price' => 3000],
            ['sku' => 'MN-003', 'name' => 'Es Kopi Susu Gula Aren', 'price' => 18000, 'cost_price' => 7000],
            ['sku' => 'MN-004', 'name' => 'Teh Tarik', 'price' => 15000, 'cost_price' => 5500],
            ['sku' => 'MN-005', 'name' => 'Air Mineral', 'price' => 5000, 'cost_price' => 2500],
        ];

        foreach ($daftarMinuman as $item) {
            Product::firstOrCreate(
                ['sku' => $item['sku']],
                ['category_id' => $minuman->id, 'name' => $item['name'], 'price' => $item['price'], 'cost_price' => $item['cost_price']],
            );
        }

        $esKopi = Product::where('sku', 'MN-003')->first();
        if ($esKopi && $esKopi->variantGroups()->doesntExist()) {
            $group = $esKopi->variantGroups()->create(['name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);
            $group->options()->createMany([
                ['name' => 'Regular', 'price_delta' => 0],
                ['name' => 'Large', 'price_delta' => 5000],
            ]);
        }

        $daftarMakanan = [
            ['sku' => 'MK-001', 'name' => 'Nasi Goreng Spesial', 'price' => 25000, 'cost_price' => 10000],
            ['sku' => 'MK-002', 'name' => 'Mie Goreng Jawa', 'price' => 22000, 'cost_price' => 9000],
            ['sku' => 'MK-003', 'name' => 'Ayam Geprek Sambal Matah', 'price' => 23000, 'cost_price' => 9500],
            ['sku' => 'MK-004', 'name' => 'Nasi Ayam Penyet', 'price' => 24000, 'cost_price' => 10000],
            ['sku' => 'MK-005', 'name' => 'Pisang Goreng Keju', 'price' => 15000, 'cost_price' => 5000],
        ];

        foreach ($daftarMakanan as $item) {
            Product::firstOrCreate(
                ['sku' => $item['sku']],
                ['category_id' => $makanan->id, 'name' => $item['name'], 'price' => $item['price'], 'cost_price' => $item['cost_price']],
            );
        }
    }
}

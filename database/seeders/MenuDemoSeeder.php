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
        $kopi = Category::firstOrCreate(['name' => 'Kopi'], ['sort_order' => 1]);
        $makanan = Category::firstOrCreate(['name' => 'Makanan'], ['sort_order' => 2]);
        $camilan = Category::firstOrCreate(['name' => 'Camilan'], ['sort_order' => 3]);

        $esKopi = Product::firstOrCreate(
            ['sku' => 'KP-001'],
            ['category_id' => $kopi->id, 'name' => 'Es Kopi Susu Gula Aren', 'price' => 18000],
        );
        if ($esKopi->variantGroups()->doesntExist()) {
            $group = $esKopi->variantGroups()->create(['name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);
            $group->options()->createMany([
                ['name' => 'Regular', 'price_delta' => 0],
                ['name' => 'Large', 'price_delta' => 5000],
            ]);
        }

        Product::firstOrCreate(
            ['sku' => 'MK-001'],
            ['category_id' => $makanan->id, 'name' => 'Nasi Goreng Spesial', 'price' => 25000],
        );
        Product::firstOrCreate(
            ['sku' => 'CM-001'],
            ['category_id' => $camilan->id, 'name' => 'Pisang Goreng', 'price' => 12000],
        );
    }
}

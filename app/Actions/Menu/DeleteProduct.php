<?php

namespace App\Actions\Menu;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProduct
{
    /** Soft delete: riwayat order tetap merujuk produk ini. */
    public function handle(Product $product): void
    {
        DB::transaction(fn () => $product->delete());
    }
}

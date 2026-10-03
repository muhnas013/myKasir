<?php

namespace App\Actions\Menu;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function handle(Category $category): void
    {
        DB::transaction(function () use ($category) {
            if ($category->products()->withTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Kategori masih memiliki produk. Pindahkan atau hapus produknya dulu.',
                ]);
            }

            $category->delete();
        });
    }
}

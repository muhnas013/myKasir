<?php

namespace App\Actions\Menu;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class SaveCategory
{
    /**
     * @param  array{name: string, sort_order?: int}  $data
     */
    public function handle(?Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $category ??= new Category;
            $category->fill([
                'name' => $data['name'],
                'sort_order' => $data['sort_order'] ?? 0,
            ])->save();

            return $category;
        });
    }
}

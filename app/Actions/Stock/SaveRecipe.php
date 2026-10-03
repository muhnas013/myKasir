<?php

namespace App\Actions\Stock;

use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SaveRecipe
{
    /**
     * Sinkron resep dasar produk + resep tiap opsi varian berdasarkan ingredient_id (baris qty<=0 dibuang).
     *
     * @param  list<array{ingredient_id: int, qty: float|string}>  $baseLines
     * @param  array<int, list<array{ingredient_id: int, qty: float|string}>>  $optionLines  variant_option_id => baris
     */
    public function handle(Product $product, array $baseLines, array $optionLines): void
    {
        DB::transaction(function () use ($product, $baseLines, $optionLines) {
            $this->sync($product->recipes(), $baseLines);

            foreach ($product->variantGroups as $group) {
                foreach ($group->options as $option) {
                    $this->sync($option->recipes(), $optionLines[$option->id] ?? []);
                }
            }
        });
    }

    /**
     * @param  list<array{ingredient_id: int, qty: float|string}>  $lines
     */
    private function sync(HasMany $relation, array $lines): void
    {
        $keep = [];
        foreach ($lines as $line) {
            if ((float) $line['qty'] <= 0) {
                continue;
            }
            $recipe = $relation->updateOrCreate(
                ['ingredient_id' => $line['ingredient_id']],
                ['qty' => $line['qty']],
            );
            $keep[] = $recipe->id;
        }
        $relation->whereNotIn('id', $keep)->delete();
    }
}

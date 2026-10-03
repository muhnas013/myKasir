<?php

namespace App\Actions\Stock;

use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;

class SaveIngredient
{
    /**
     * Hanya metadata bahan; stock_qty & avg_cost berubah lewat StockService (stok masuk/opname).
     *
     * @param  array{name: string, unit: string, min_qty: float|int|string}  $data
     */
    public function handle(?Ingredient $ingredient, array $data): Ingredient
    {
        return DB::transaction(function () use ($ingredient, $data) {
            $ingredient ??= new Ingredient;
            $ingredient->fill([
                'name' => $data['name'],
                'unit' => $data['unit'],
                'min_qty' => $data['min_qty'],
            ])->save();

            return $ingredient;
        });
    }
}

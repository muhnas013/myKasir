<?php

namespace App\Actions\Stock;

use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteIngredient
{
    public function handle(Ingredient $ingredient): void
    {
        DB::transaction(function () use ($ingredient) {
            if ($ingredient->recipes()->exists()) {
                throw ValidationException::withMessages([
                    'ingredient' => 'Bahan masih dipakai di resep. Hapus dari resep dulu.',
                ]);
            }

            $ingredient->delete();
        });
    }
}

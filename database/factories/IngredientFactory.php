<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'unit' => 'g',
            'stock_qty' => 1000,
            'min_qty' => 100,
            'avg_cost' => 10000,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\WageActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WageActivity>
 */
class WageActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'bonus_amount' => 5000,
            'is_active' => true,
        ];
    }
}

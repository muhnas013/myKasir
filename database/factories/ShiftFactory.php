<?php

namespace Database\Factories;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->cashier(),
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash' => 200000,
        ];
    }
}

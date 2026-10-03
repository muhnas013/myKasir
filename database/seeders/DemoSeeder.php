<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed demo accounts for local development only.
     */
    public function run(): void
    {
        User::factory()->owner()->create([
            'name' => 'Pemilik Demo',
            'email' => 'owner@mykasir.test',
        ]);

        User::factory()->cashier()->create([
            'name' => 'Kasir 1',
            'pin_hash' => Hash::make('123456'),
        ]);

        $this->call(MenuDemoSeeder::class);
    }
}

<?php

namespace App\Actions\Settings;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUser
{
    /**
     * @param  array{name: string, role: string, email?: ?string, password?: ?string, pin: string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create([
                'name' => $data['name'],
                'role' => Role::from($data['role']),
                'email' => $data['email'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'pin_hash' => Hash::make($data['pin']),
                'is_active' => true,
            ]);
        });
    }
}

<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginWithPassword
{
    public function handle(string $email, string $password): User
    {
        $user = User::query()
            ->where('email', $email)
            ->whereIn('role', [Role::Owner, Role::Admin])
            ->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun ini sudah dinonaktifkan.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user);

        return $user;
    }
}

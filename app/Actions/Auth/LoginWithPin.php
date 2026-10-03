<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginWithPin
{
    public function handle(int $userId, string $pin): User
    {
        $maxAttempts = (int) config('mykasir.pin_max_attempts');
        $lockSeconds = (int) config('mykasir.pin_lock_minutes') * 60;
        $key = "pin-login:{$userId}";

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'pin' => 'Terlalu banyak percobaan. Coba lagi setelah beberapa menit.',
            ]);
        }

        $user = User::query()
            ->where('id', $userId)
            ->whereIn('role', [Role::Cashier, Role::Admin])
            ->first();

        if (! $user || ! $user->is_active || ! $user->pin_hash || ! Hash::check($pin, $user->pin_hash)) {
            RateLimiter::hit($key, $lockSeconds);

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                AuditLog::create([
                    'user_id' => $userId,
                    'action' => 'auth.pin_locked',
                    'subject_type' => User::class,
                    'subject_id' => $userId,
                    'ip' => request()->ip(),
                ]);
            }

            throw ValidationException::withMessages([
                'pin' => 'PIN salah.',
            ]);
        }

        RateLimiter::clear($key);
        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user);

        return $user;
    }
}

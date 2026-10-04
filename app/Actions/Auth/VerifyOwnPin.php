<?php

namespace App\Actions\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Konfirmasi ulang PIN milik sendiri (mis. saat Buka Shift) — bukan ganti sesi,
 * hanya penegasan bahwa yang sedang login memang orangnya. Rate limit sama
 * dengan PIN lain: 5x salah → terkunci 15 menit + audit auth.pin_locked.
 */
class VerifyOwnPin
{
    public function handle(User $user, string $pin): void
    {
        $maxAttempts = (int) config('mykasir.pin_max_attempts');
        $lockSeconds = (int) config('mykasir.pin_lock_minutes') * 60;
        $key = "pin-self:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'pin' => 'Terlalu banyak percobaan. Coba lagi setelah beberapa menit.',
            ]);
        }

        if (! $user->pin_hash || ! Hash::check($pin, $user->pin_hash)) {
            RateLimiter::hit($key, $lockSeconds);

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'auth.pin_locked',
                    'subject_type' => 'user',
                    'subject_id' => $user->id,
                    'new_values' => ['context' => 'shift_open'],
                    'ip' => request()->ip(),
                ]);
            }

            throw ValidationException::withMessages(['pin' => 'PIN salah.']);
        }

        RateLimiter::clear($key);
    }
}

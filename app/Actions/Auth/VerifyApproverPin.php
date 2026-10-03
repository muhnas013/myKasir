<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class VerifyApproverPin
{
    /**
     * Cocokkan PIN dengan owner/admin aktif. Mengembalikan id penyetuju untuk dicatat.
     * Rate limit per pemohon: 5x salah → terkunci 15 menit + audit auth.pin_locked.
     */
    public function handle(User $requester, string $pin): int
    {
        $maxAttempts = (int) config('mykasir.pin_max_attempts');
        $lockSeconds = (int) config('mykasir.pin_lock_minutes') * 60;
        $key = "pin-approval:{$requester->id}";

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'approver_pin' => 'Terlalu banyak percobaan. Coba lagi setelah beberapa menit.',
            ]);
        }

        $approver = User::query()
            ->whereIn('role', [Role::Owner, Role::Admin])
            ->where('is_active', true)
            ->whereNotNull('pin_hash')
            ->get()
            ->first(fn (User $user) => Hash::check($pin, $user->pin_hash));

        if (! $approver) {
            RateLimiter::hit($key, $lockSeconds);

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                AuditLog::create([
                    'user_id' => $requester->id,
                    'action' => 'auth.pin_locked',
                    'subject_type' => 'user',
                    'subject_id' => $requester->id,
                    'new_values' => ['context' => 'approval'],
                    'ip' => request()->ip(),
                ]);
            }

            throw ValidationException::withMessages(['approver_pin' => 'PIN persetujuan salah.']);
        }

        RateLimiter::clear($key);

        return $approver->id;
    }
}

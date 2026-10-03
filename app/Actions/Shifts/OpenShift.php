<?php

namespace App\Actions\Shifts;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenShift
{
    public function handle(User $user, int $openingCash): Shift
    {
        if ($openingCash < 0) {
            throw ValidationException::withMessages(['opening_cash' => 'Modal awal tidak boleh negatif.']);
        }

        return DB::transaction(function () use ($user, $openingCash) {
            // Kunci baris user agar dua permintaan bersamaan tidak membuka dua shift.
            User::query()->lockForUpdate()->findOrFail($user->id);

            if (Shift::where('user_id', $user->id)->where('status', ShiftStatus::Open)->exists()) {
                throw ValidationException::withMessages(['opening_cash' => 'Anda masih memiliki shift yang terbuka.']);
            }

            return Shift::create([
                'user_id' => $user->id,
                'status' => ShiftStatus::Open,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
            ]);
        });
    }
}

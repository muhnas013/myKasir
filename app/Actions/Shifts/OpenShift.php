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

            $now = now();

            // Jam operasional outlet 08:00-22:00; berlaku untuk shift pertama hari itu juga.
            if ($now->hour < 8 || $now->hour >= 22) {
                throw ValidationException::withMessages(['opening_cash' => 'Outlet buka jam 08:00–22:00. Shift tidak bisa dibuka di luar jam operasional.']);
            }

            if (Shift::where('user_id', $user->id)->where('status', ShiftStatus::Open)->exists()) {
                throw ValidationException::withMessages(['opening_cash' => 'Anda masih memiliki shift yang terbuka.']);
            }

            // Maksimal 2 shift per hari untuk seluruh outlet (lintas kasir), bukan per kasir.
            $shiftsToday = Shift::query()->whereDate('opened_at', $now->toDateString())->lockForUpdate()->count();

            if ($shiftsToday >= 2) {
                throw ValidationException::withMessages(['opening_cash' => 'Sudah 2 shift hari ini. Shift baru bisa dibuka besok mulai jam 08:00.']);
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

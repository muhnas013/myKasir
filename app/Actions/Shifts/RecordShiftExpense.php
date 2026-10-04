<?php

namespace App\Actions\Shifts;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\ShiftExpense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordShiftExpense
{
    public function handle(Shift $shift, User $actor, string $description, int $amount): ShiftExpense
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal harus lebih dari 0.']);
        }

        if (trim($description) === '') {
            throw ValidationException::withMessages(['description' => 'Keterangan wajib diisi.']);
        }

        return DB::transaction(function () use ($shift, $actor, $description, $amount) {
            $shift = Shift::query()->lockForUpdate()->findOrFail($shift->id);

            if ($shift->status !== ShiftStatus::Open) {
                throw ValidationException::withMessages(['shift' => 'Shift sudah ditutup.']);
            }

            if ($shift->user_id !== $actor->id) {
                throw ValidationException::withMessages(['shift' => 'Hanya pemilik shift yang dapat mencatat pengeluaran.']);
            }

            return $shift->expenses()->create([
                'description' => mb_substr(trim($description), 0, 255),
                'amount' => $amount,
            ]);
        });
    }
}

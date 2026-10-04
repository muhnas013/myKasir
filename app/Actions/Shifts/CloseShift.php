<?php

namespace App\Actions\Shifts;

use App\Enums\OrderStatus;
use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use App\Models\WageActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseShift
{
    /** @param  list<int>  $activityIds  id wage_activities yang dilakukan kasir selama shift (06 P8) */
    public function handle(Shift $shift, User $actor, int $countedCash, ?string $note = null, array $activityIds = []): Shift
    {
        if ($countedCash < 0) {
            throw ValidationException::withMessages(['counted_cash' => 'Kas fisik tidak boleh negatif.']);
        }

        return DB::transaction(function () use ($shift, $actor, $countedCash, $note, $activityIds) {
            $shift = Shift::query()->lockForUpdate()->findOrFail($shift->id);

            if ($shift->user_id !== $actor->id) {
                throw ValidationException::withMessages(['shift' => 'Hanya pemilik shift yang dapat menutupnya.']);
            }

            if ($shift->status !== ShiftStatus::Open) {
                throw ValidationException::withMessages(['shift' => 'Shift sudah ditutup.']);
            }

            if ($shift->orders()->where('status', OrderStatus::Open)->exists()) {
                throw ValidationException::withMessages(['shift' => 'Masih ada pesanan tersimpan. Bayar atau batalkan dulu sebelum menutup shift.']);
            }

            $expected = $shift->expectedCash();
            $difference = $countedCash - $expected;

            if ($difference !== 0 && trim((string) $note) === '') {
                throw ValidationException::withMessages(['closing_note' => 'Catatan wajib diisi bila ada selisih kas.']);
            }

            $shift->update([
                'status' => ShiftStatus::Closed,
                'closed_at' => now(),
                'expected_cash' => $expected,
                'counted_cash' => $countedCash,
                'cash_difference' => $difference,
                'closing_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
                'closed_by' => $actor->id,
            ]);

            if ($activityIds !== []) {
                WageActivity::query()->active()->whereIn('id', $activityIds)->get()->each(
                    fn (WageActivity $activity) => $shift->activities()->create([
                        'wage_activity_id' => $activity->id,
                        'name' => $activity->name,
                        'bonus_amount' => $activity->bonus_amount,
                    ])
                );
            }

            return $shift;
        });
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\ShiftStatus;
use App\Models\AuditLog;
use App\Models\Shift;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Tutup paksa shift yang masih terbuka jam 22:00 (06 P2 — jam operasional outlet).
 * Kas fisik tidak pernah dihitung otomatis: counted_cash disamakan dengan expected_cash
 * (selisih 0) dan diberi catatan khusus supaya pemilik tahu ini bukan hitungan manual.
 */
class AutoCloseOpenShifts extends Command
{
    protected $signature = 'shifts:auto-close';

    protected $description = 'Tutup otomatis semua shift yang masih terbuka pukul 22:00';

    public function handle(): int
    {
        $openShifts = Shift::query()->where('status', ShiftStatus::Open)->get();

        foreach ($openShifts as $shift) {
            DB::transaction(function () use ($shift) {
                $shift = Shift::query()->lockForUpdate()->findOrFail($shift->id);

                if ($shift->status !== ShiftStatus::Open) {
                    return;
                }

                if ($shift->orders()->where('status', OrderStatus::Open)->exists()) {
                    $this->warn("Shift {$shift->id} dilewati: masih ada pesanan belum selesai, perlu ditinjau manual.");

                    return;
                }

                $expected = $shift->expectedCash();

                $shift->update([
                    'status' => ShiftStatus::Closed,
                    'closed_at' => now(),
                    'expected_cash' => $expected,
                    'counted_cash' => $expected,
                    'cash_difference' => 0,
                    'closing_note' => 'Ditutup otomatis sistem pukul 22:00 — kas belum dihitung fisik, mohon verifikasi.',
                    'closed_by' => null,
                ]);

                AuditLog::create([
                    'user_id' => null,
                    'approved_by' => null,
                    'action' => 'shift.auto_closed',
                    'subject_type' => 'shift',
                    'subject_id' => $shift->id,
                    'old_values' => ['status' => 'open'],
                    'new_values' => ['status' => 'closed', 'expected_cash' => $expected],
                    'ip' => null,
                ]);

                $this->info("Shift {$shift->id} ditutup otomatis (kas: {$expected}).");
            });
        }

        return self::SUCCESS;
    }
}

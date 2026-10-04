<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ShiftStatus;
use App\Models\Order;
use App\Models\Shift;
use App\Models\ShiftActivity;
use Illuminate\Support\Collection;

/**
 * Hitung upah per shift (06 P8): upah dasar + bonus aktivitas + bonus
 * penjualan berjenjang. Hanya shift berstatus closed yang dihitung.
 */
class WageCalculator
{
    public function __construct(private SettingService $settings) {}

    /** @return Collection<int, array<string, mixed>> satu baris per shift, terurut opened_at */
    public function forRange(string $startDate, string $endDate): Collection
    {
        $shifts = Shift::query()
            ->where('status', ShiftStatus::Closed)
            ->whereBetween('opened_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->with('user')
            ->orderBy('opened_at')
            ->get();

        if ($shifts->isEmpty()) {
            return collect();
        }

        $shiftIds = $shifts->pluck('id');

        $omzetByShift = Order::query()
            ->whereIn('shift_id', $shiftIds)
            ->where('status', OrderStatus::Paid)
            ->selectRaw('shift_id, SUM(total) as omzet')
            ->groupBy('shift_id')
            ->pluck('omzet', 'shift_id')
            ->map(fn ($v) => (int) $v);

        $activitiesByShift = ShiftActivity::query()
            ->whereIn('shift_id', $shiftIds)
            ->get()
            ->groupBy('shift_id');

        $base = (int) $this->settings->get('wage_base_per_shift', 35000);
        $bonusPer100k = (int) $this->settings->get('wage_sales_bonus_per_100k', 5000);
        $minRevenue = (int) $this->settings->get('wage_sales_bonus_min_revenue', 800000);

        $salesBonusByShift = $this->salesBonusByShift($shifts, $omzetByShift, $bonusPer100k, $minRevenue);

        return $shifts->map(function (Shift $shift) use ($omzetByShift, $activitiesByShift, $base, $salesBonusByShift) {
            $omzet = $omzetByShift[$shift->id] ?? 0;
            $activities = ($activitiesByShift[$shift->id] ?? collect())
                ->map(fn (ShiftActivity $a) => ['name' => $a->name, 'bonus_amount' => $a->bonus_amount])
                ->values();
            $activityTotal = $activities->sum('bonus_amount');
            $salesBonus = $salesBonusByShift[$shift->id] ?? 0;

            return [
                'shift_id' => $shift->id,
                'user_name' => $shift->user->name,
                'date' => $shift->opened_at->toDateString(),
                'opened_at' => $shift->opened_at,
                'closed_at' => $shift->closed_at,
                'omzet' => $omzet,
                'base' => $base,
                'activities' => $activities,
                'activity_total' => $activityTotal,
                'sales_bonus' => $salesBonus,
                'total' => $base + $activityTotal + $salesBonus,
            ];
        })->values();
    }

    /** @return array<string, array<string, mixed>> nama pegawai => {shifts, total} */
    public function summaryByUser(Collection $rows): array
    {
        return $rows->groupBy('user_name')
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'shifts' => $rows->count(),
                'total' => $rows->sum('total'),
            ])
            ->sortKeys()
            ->all();
    }

    /**
     * @param  Collection<int, Shift>  $shifts
     * @param  Collection<int, int>  $omzetByShift
     * @return array<int, int> shift_id => bonus_penjualan
     */
    private function salesBonusByShift(Collection $shifts, Collection $omzetByShift, int $bonusPer100k, int $minRevenue): array
    {
        $result = [];

        foreach ($shifts->groupBy(fn (Shift $s) => $s->opened_at->toDateString()) as $dayShifts) {
            $dayTotal = $dayShifts->sum(fn (Shift $s) => $omzetByShift[$s->id] ?? 0);

            if ($dayTotal < $minRevenue) {
                foreach ($dayShifts as $shift) {
                    $result[$shift->id] = 0;
                }

                continue;
            }

            foreach ($dayShifts as $shift) {
                $omzet = $omzetByShift[$shift->id] ?? 0;
                $rounded = intdiv($omzet, 100000) * 100000;
                $result[$shift->id] = intdiv($rounded, 100000) * $bonusPer100k;
            }
        }

        return $result;
    }
}

<?php

namespace Tests\Feature\Payroll;

use App\Enums\OrderStatus;
use App\Enums\ShiftStatus;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use App\Models\WageActivity;
use App\Services\WageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WageCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function closedShift(User $user, string $openedAt): Shift
    {
        return Shift::create([
            'user_id' => $user->id,
            'status' => ShiftStatus::Closed,
            'opened_at' => $openedAt,
            'closed_at' => $openedAt,
            'opening_cash' => 0,
            'expected_cash' => 0,
            'counted_cash' => 0,
            'cash_difference' => 0,
        ]);
    }

    private function paidOrder(Shift $shift, int $total): Order
    {
        static $seq = 0;
        $seq++;

        return Order::create([
            'number' => 'T-'.$seq,
            'business_date' => $shift->opened_at->toDateString(),
            'shift_id' => $shift->id,
            'user_id' => $shift->user_id,
            'status' => OrderStatus::Paid,
            'order_type' => 'take_away',
            'subtotal' => $total,
            'total' => $total,
            'idempotency_key' => (string) Str::uuid(),
            'paid_at' => $shift->opened_at,
        ]);
    }

    public function test_base_wage_only_when_below_sales_threshold(): void
    {
        $cashier = User::factory()->cashier()->create();
        $shift = $this->closedShift($cashier, '2026-10-01 08:00:00');
        $this->paidOrder($shift, 100000);

        $rows = app(WageCalculator::class)->forRange('2026-10-01', '2026-10-01');

        $this->assertCount(1, $rows);
        $this->assertSame(35000, $rows[0]['base']);
        $this->assertSame(0, $rows[0]['sales_bonus']);
        $this->assertSame(35000, $rows[0]['total']);
    }

    public function test_matches_documented_example_two_shifts_same_day(): void
    {
        $a = User::factory()->cashier()->create(['name' => 'Kasir A']);
        $b = User::factory()->cashier()->create(['name' => 'Kasir B']);
        $shiftA = $this->closedShift($a, '2026-10-01 07:00:00');
        $shiftB = $this->closedShift($b, '2026-10-01 14:00:00');
        $this->paidOrder($shiftA, 350000);
        $this->paidOrder($shiftB, 450000);

        $rows = app(WageCalculator::class)->forRange('2026-10-01', '2026-10-01')->keyBy('shift_id');

        $this->assertSame(15000, $rows[$shiftA->id]['sales_bonus']);
        $this->assertSame(50000, $rows[$shiftA->id]['total']); // 35000 + 15000
        $this->assertSame(20000, $rows[$shiftB->id]['sales_bonus']);
        $this->assertSame(55000, $rows[$shiftB->id]['total']); // 35000 + 20000
    }

    public function test_same_revenue_split_across_different_days_does_not_pool(): void
    {
        $cashier = User::factory()->cashier()->create();
        $shiftDay1 = $this->closedShift($cashier, '2026-10-01 08:00:00');
        $shiftDay2 = $this->closedShift($cashier, '2026-10-02 08:00:00');
        $this->paidOrder($shiftDay1, 350000);
        $this->paidOrder($shiftDay2, 450000);

        $rows = app(WageCalculator::class)->forRange('2026-10-01', '2026-10-02')->keyBy('shift_id');

        $this->assertSame(0, $rows[$shiftDay1->id]['sales_bonus']);
        $this->assertSame(0, $rows[$shiftDay2->id]['sales_bonus']);
    }

    public function test_activity_bonus_is_included_in_total(): void
    {
        $cashier = User::factory()->cashier()->create();
        $shift = $this->closedShift($cashier, '2026-10-01 08:00:00');
        $jelly = WageActivity::factory()->create(['name' => 'Pembuatan Jelly', 'bonus_amount' => 5000]);
        $shift->activities()->create(['wage_activity_id' => $jelly->id, 'name' => $jelly->name, 'bonus_amount' => $jelly->bonus_amount]);

        $rows = app(WageCalculator::class)->forRange('2026-10-01', '2026-10-01');

        $this->assertSame(5000, $rows[0]['activity_total']);
        $this->assertSame(40000, $rows[0]['total']); // 35000 + 5000
    }

    public function test_open_shift_is_excluded(): void
    {
        $cashier = User::factory()->cashier()->create();
        Shift::create([
            'user_id' => $cashier->id,
            'status' => ShiftStatus::Open,
            'opened_at' => '2026-10-01 08:00:00',
            'opening_cash' => 0,
        ]);

        $rows = app(WageCalculator::class)->forRange('2026-10-01', '2026-10-01');

        $this->assertCount(0, $rows);
    }

    public function test_summary_by_user_aggregates_total(): void
    {
        $cashier = User::factory()->cashier()->create(['name' => 'Kasir A']);
        $shift1 = $this->closedShift($cashier, '2026-10-01 08:00:00');
        $shift2 = $this->closedShift($cashier, '2026-10-02 08:00:00');

        $rows = app(WageCalculator::class)->forRange('2026-10-01', '2026-10-02');
        $summary = app(WageCalculator::class)->summaryByUser($rows);

        $this->assertSame(2, $summary['Kasir A']['shifts']);
        $this->assertSame(70000, $summary['Kasir A']['total']); // 2 x 35000
    }
}

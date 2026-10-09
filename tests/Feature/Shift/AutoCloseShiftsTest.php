<?php

namespace Tests\Feature\Shift;

use App\Actions\Orders\CompleteOrder;
use App\Enums\ShiftStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class AutoCloseShiftsTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();
    }

    public function test_open_shift_without_pending_orders_is_closed_automatically(): void
    {
        app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $this->shift->refresh();

        $this->assertSame(ShiftStatus::Closed, $this->shift->status);
        $this->assertSame($this->shift->expected_cash, $this->shift->counted_cash);
        $this->assertSame(0, $this->shift->cash_difference);
        $this->assertNull($this->shift->closed_by);
        $this->assertStringContainsString('Ditutup otomatis sistem', $this->shift->closing_note);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'shift.auto_closed',
            'subject_type' => 'shift',
            'subject_id' => $this->shift->id,
        ]);
    }

    public function test_shift_with_pending_order_is_skipped(): void
    {
        Order::create([
            'number' => 'T-PENDING',
            'business_date' => now()->toDateString(),
            'shift_id' => $this->shift->id,
            'user_id' => $this->cashier->id,
            'status' => 'open',
            'order_type' => 'take_away',
            'subtotal' => 10000,
            'total' => 10000,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->artisan('shifts:auto-close')->assertSuccessful();

        $this->shift->refresh();

        $this->assertSame(ShiftStatus::Open, $this->shift->status);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'shift.auto_closed', 'subject_id' => $this->shift->id]);
    }
}

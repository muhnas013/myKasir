<?php

namespace Tests\Feature\Shift;

use App\Actions\Auth\VerifyOwnPin;
use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\SaveOpenOrder;
use App\Actions\Shifts\CloseShift;
use App\Actions\Shifts\OpenShift;
use App\Actions\Shifts\RecordShiftExpense;
use App\Models\User;
use App\Models\WageActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class ShiftActionsTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();
    }

    public function test_user_cannot_open_second_shift_while_one_is_open(): void
    {
        $this->expectException(ValidationException::class);
        app(OpenShift::class)->handle($this->cashier, 1000);
    }

    public function test_negative_opening_cash_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(OpenShift::class)->handle(User::factory()->cashier()->create(), -1);
    }

    public function test_expected_cash_and_negative_difference_require_note(): void
    {
        app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        try {
            app(CloseShift::class)->handle($this->shift, $this->cashier, 280000);
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('closing_note', $e->errors());
        }

        $closed = app(CloseShift::class)->handle($this->shift, $this->cashier, 280000, 'Uang kembalian kurang');

        $this->assertSame('closed', $closed->status->value);
        $this->assertSame(280300, $closed->expected_cash);
        $this->assertSame(280000, $closed->counted_cash);
        $this->assertSame(-300, $closed->cash_difference);
        $this->assertSame('Uang kembalian kurang', $closed->closing_note);
        $this->assertSame($this->cashier->id, $closed->closed_by);
        $this->assertNotNull($closed->closed_at);
    }

    public function test_zero_difference_needs_no_note(): void
    {
        app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        $closed = app(CloseShift::class)->handle($this->shift, $this->cashier, 280300);

        $this->assertSame(0, $closed->cash_difference);
        $this->assertNull($closed->closing_note);
    }

    public function test_non_cash_payments_do_not_affect_expected_cash(): void
    {
        app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput(['method' => 'qris']));

        $this->assertSame(200000, $this->shift->expectedCash());
    }

    public function test_open_orders_block_closing(): void
    {
        app(SaveOpenOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        $this->expectException(ValidationException::class);
        app(CloseShift::class)->handle($this->shift, $this->cashier, 200000);
    }

    public function test_closed_shift_cannot_be_closed_again(): void
    {
        app(CloseShift::class)->handle($this->shift, $this->cashier, 200000);

        $this->expectException(ValidationException::class);
        app(CloseShift::class)->handle($this->shift, $this->cashier, 200000);
    }

    public function test_other_user_cannot_close_someone_elses_shift(): void
    {
        $this->expectException(ValidationException::class);
        app(CloseShift::class)->handle($this->shift, User::factory()->cashier()->create(), 200000);
    }

    public function test_selected_activities_are_logged_with_snapshot(): void
    {
        $jelly = WageActivity::factory()->create(['name' => 'Pembuatan Jelly', 'bonus_amount' => 5000]);
        $inactive = WageActivity::factory()->create(['name' => 'Nonaktif', 'bonus_amount' => 9999, 'is_active' => false]);

        $closed = app(CloseShift::class)->handle($this->shift, $this->cashier, 200000, null, [$jelly->id, $inactive->id]);

        $this->assertDatabaseHas('shift_activities', [
            'shift_id' => $closed->id,
            'wage_activity_id' => $jelly->id,
            'name' => 'Pembuatan Jelly',
            'bonus_amount' => 5000,
        ]);
        $this->assertDatabaseMissing('shift_activities', ['wage_activity_id' => $inactive->id]);

        $jelly->update(['name' => 'Berubah', 'bonus_amount' => 1]);
        $this->assertDatabaseHas('shift_activities', ['shift_id' => $closed->id, 'name' => 'Pembuatan Jelly', 'bonus_amount' => 5000]);
    }

    public function test_expense_reduces_expected_cash(): void
    {
        app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        app(RecordShiftExpense::class)->handle($this->shift, $this->cashier, 'Es batu', 15000);
        app(RecordShiftExpense::class)->handle($this->shift, $this->cashier, 'Gula', 20000);

        // Modal 200.000 + tunai bersih 80.300 - pengeluaran 35.000 = 245.300
        $this->assertSame(245300, $this->shift->fresh()->expectedCash());

        $closed = app(CloseShift::class)->handle($this->shift, $this->cashier, 245300);
        $this->assertSame(0, $closed->cash_difference);
    }

    public function test_expense_requires_positive_amount_and_description(): void
    {
        $this->expectException(ValidationException::class);
        app(RecordShiftExpense::class)->handle($this->shift, $this->cashier, '', 15000);
    }

    public function test_expense_cannot_be_recorded_on_closed_shift(): void
    {
        app(CloseShift::class)->handle($this->shift, $this->cashier, 200000);

        $this->expectException(ValidationException::class);
        app(RecordShiftExpense::class)->handle($this->shift, $this->cashier, 'Es batu', 15000);
    }

    public function test_expense_cannot_be_recorded_by_another_user(): void
    {
        $this->expectException(ValidationException::class);
        app(RecordShiftExpense::class)->handle($this->shift, User::factory()->cashier()->create(), 'Es batu', 15000);
    }

    public function test_correct_own_pin_passes_verification(): void
    {
        $this->expectNotToPerformAssertions();
        app(VerifyOwnPin::class)->handle($this->cashier, '135790');
    }

    public function test_wrong_own_pin_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(VerifyOwnPin::class)->handle($this->cashier, '000000');
    }

    public function test_five_wrong_own_pins_lock_and_audit(): void
    {
        RateLimiter::clear('pin-self:'.$this->cashier->id);

        for ($i = 0; $i < 5; $i++) {
            try {
                app(VerifyOwnPin::class)->handle($this->cashier, '000000');
            } catch (ValidationException) {
            }
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.pin_locked', 'user_id' => $this->cashier->id]);

        $this->expectException(ValidationException::class);
        app(VerifyOwnPin::class)->handle($this->cashier, '135790');
    }

    public function test_new_shift_can_be_opened_after_closing(): void
    {
        app(CloseShift::class)->handle($this->shift, $this->cashier, 200000);

        $shift = app(OpenShift::class)->handle($this->cashier, 150000);

        $this->assertSame('open', $shift->status->value);
    }
}

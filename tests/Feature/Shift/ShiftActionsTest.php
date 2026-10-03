<?php

namespace Tests\Feature\Shift;

use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\SaveOpenOrder;
use App\Actions\Shifts\CloseShift;
use App\Actions\Shifts\OpenShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_new_shift_can_be_opened_after_closing(): void
    {
        app(CloseShift::class)->handle($this->shift, $this->cashier, 200000);

        $shift = app(OpenShift::class)->handle($this->cashier, 150000);

        $this->assertSame('open', $shift->status->value);
    }
}

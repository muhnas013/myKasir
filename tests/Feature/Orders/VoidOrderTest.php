<?php

namespace Tests\Feature\Orders;

use App\Actions\Auth\VerifyApproverPin;
use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\VoidOrder;
use App\Actions\Shifts\CloseShift;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class VoidOrderTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();
        $this->pisangGoreng->update(['track_stock' => true, 'stock_qty' => 5]);
        $this->order = app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());
    }

    public function test_cashier_without_approver_cannot_void(): void
    {
        try {
            app(VoidOrder::class)->handle($this->order, $this->cashier, 'Salah input pesanan');
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approver_pin', $e->errors());
        }

        $this->assertSame('paid', $this->order->fresh()->status->value);
    }

    public function test_cashier_with_admin_pin_voids_returns_stock_and_reduces_expected_cash(): void
    {
        $admin = $this->admin('246813');
        $this->assertSame(280300, $this->shift->expectedCash());
        $this->assertSame(4, $this->pisangGoreng->fresh()->stock_qty);

        $approverId = app(VerifyApproverPin::class)->handle($this->cashier, '246813');
        app(VoidOrder::class)->handle($this->order, $this->cashier, 'Salah input pesanan', $approverId);

        $order = $this->order->fresh();
        $this->assertSame('void', $order->status->value);
        $this->assertSame($this->cashier->id, $order->voided_by);
        $this->assertSame(5, $this->pisangGoreng->fresh()->stock_qty);
        $this->assertSame(200000, $this->shift->expectedCash());
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'type' => 'void_return']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.void', 'approved_by' => $admin->id, 'subject_id' => $order->id]);
    }

    public function test_reason_must_be_at_least_five_characters(): void
    {
        $this->expectException(ValidationException::class);
        app(VoidOrder::class)->handle($this->order, $this->admin(), 'oops');
    }

    public function test_admin_can_void_without_extra_pin(): void
    {
        $admin = $this->admin();

        app(VoidOrder::class)->handle($this->order, $admin, 'Pelanggan membatalkan');

        $this->assertSame('void', $this->order->fresh()->status->value);
    }

    public function test_already_void_order_cannot_be_voided_again(): void
    {
        $admin = $this->admin();
        app(VoidOrder::class)->handle($this->order, $admin, 'Pelanggan membatalkan');

        $this->expectException(ValidationException::class);
        app(VoidOrder::class)->handle($this->order, $admin, 'Pelanggan membatalkan');
    }

    public function test_closed_shift_order_can_only_be_voided_by_owner(): void
    {
        app(CloseShift::class)->handle($this->shift, $this->cashier, 280300);

        try {
            app(VoidOrder::class)->handle($this->order, $this->admin(), 'Koreksi setelah tutup');
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('order', $e->errors());
        }

        $owner = User::factory()->owner()->create();
        app(VoidOrder::class)->handle($this->order, $owner, 'Koreksi setelah tutup');

        $this->assertSame('void', $this->order->fresh()->status->value);
    }

    public function test_wrong_approver_pin_is_rejected(): void
    {
        $this->admin('246813');

        $this->expectException(ValidationException::class);
        app(VerifyApproverPin::class)->handle($this->cashier, '999111');
    }

    public function test_cashier_pin_is_not_accepted_as_approver_pin(): void
    {
        $this->cashier->forceFill(['pin_hash' => bcrypt('135792')])->save();

        $this->expectException(ValidationException::class);
        app(VerifyApproverPin::class)->handle($this->cashier, '135792');
    }

    public function test_five_wrong_pins_lock_approval_for_requester_and_audit(): void
    {
        $this->admin('246813');
        RateLimiter::clear('pin-approval:'.$this->cashier->id);

        for ($i = 0; $i < 5; $i++) {
            try {
                app(VerifyApproverPin::class)->handle($this->cashier, '000111');
            } catch (ValidationException) {
            }
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.pin_locked', 'user_id' => $this->cashier->id]);

        // PIN benar pun ditolak selama terkunci
        $this->expectException(ValidationException::class);
        app(VerifyApproverPin::class)->handle($this->cashier, '246813');
    }

    public function test_inactive_admin_pin_is_not_accepted(): void
    {
        $this->admin('246813')->update(['is_active' => false]);

        $this->expectException(ValidationException::class);
        app(VerifyApproverPin::class)->handle($this->cashier, '246813');
    }
}

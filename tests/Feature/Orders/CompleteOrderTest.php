<?php

namespace Tests\Feature\Orders;

use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\DiscardOpenOrder;
use App\Actions\Orders\SaveOpenOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\VariantGroup;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class CompleteOrderTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();
    }

    private function complete(array $override = [])
    {
        return app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput($override));
    }

    public function test_cash_order_is_totaled_and_persisted(): void
    {
        $order = $this->complete();

        $this->assertSame('paid', $order->status->value);
        $this->assertSame(73000, $order->subtotal);
        $this->assertSame(7300, $order->tax);
        $this->assertSame(80300, $order->total);
        $this->assertCount(3, $order->items);
        $this->assertSame(19700, $order->payment->change_amount);
        $this->assertMatchesRegularExpression('/^A-\d{4}$/', $order->number);
        $this->assertNotNull($order->paid_at);
    }

    public function test_dine_in_with_service_matches_acceptance_numbers(): void
    {
        app(SettingService::class)->set('service_enabled', true);

        $order = $this->complete(['order_type' => 'dine_in']);

        $this->assertSame(3650, $order->service);
        $this->assertSame(7665, $order->tax);
        $this->assertSame(-15, $order->rounding);
        $this->assertSame(84300, $order->total);
    }

    public function test_client_supplied_prices_are_ignored(): void
    {
        $order = $this->complete(['lines' => [
            ['product_id' => $this->esKopi->id, 'qty' => 1, 'price' => 1, 'unit_price' => 1, 'total' => 1],
        ], 'total' => 1]);

        $this->assertSame(18000, $order->items->first()->unit_price);
        $this->assertSame(19800, $order->total);
    }

    public function test_variant_option_price_is_added_and_snapshotted(): void
    {
        $group = VariantGroup::create(['product_id' => $this->esKopi->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);
        $large = $group->options()->create(['name' => 'Large', 'price_delta' => 5000]);

        $order = $this->complete(['lines' => [
            ['product_id' => $this->esKopi->id, 'qty' => 1, 'option_ids' => [$large->id]],
        ]]);

        $item = $order->items->first();
        $this->assertSame(23000, $item->unit_price);
        $this->assertSame([['id' => $large->id, 'name' => 'Large', 'price_delta' => 5000]], $item->options);
    }

    public function test_missing_required_variant_is_rejected(): void
    {
        VariantGroup::create(['product_id' => $this->esKopi->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);

        $this->expectException(ValidationException::class);
        $this->complete(['lines' => [['product_id' => $this->esKopi->id, 'qty' => 1]]]);
    }

    public function test_old_order_items_keep_price_after_product_price_change(): void
    {
        $order = $this->complete();
        $this->esKopi->update(['price' => 99000]);

        $this->assertSame(18000, $order->fresh()->items->firstWhere('product_id', $this->esKopi->id)->unit_price);
        $this->assertSame(80300, $order->fresh()->total);
    }

    public function test_cash_below_total_is_rejected_and_nothing_is_written(): void
    {
        try {
            $this->complete(['paid_amount' => 80000]);
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('paid_amount', $e->errors());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_card_without_reference_is_rejected(): void
    {
        try {
            $this->complete(['method' => 'card']);
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reference', $e->errors());
        }

        $order = $this->complete(['method' => 'card', 'reference' => 'TRX-123']);
        $this->assertSame('TRX-123', $order->payment->reference);
        $this->assertSame(0, $order->payment->change_amount);
    }

    public function test_qris_is_paid_exactly_total(): void
    {
        $order = $this->complete(['method' => 'qris', 'paid_amount' => 0]);

        $this->assertSame($order->total, $order->payment->paid_amount);
    }

    public function test_disabled_payment_method_is_rejected(): void
    {
        app(SettingService::class)->set('method_qris', false);

        $this->expectException(ValidationException::class);
        $this->complete(['method' => 'qris']);
    }

    public function test_same_idempotency_key_creates_exactly_one_order(): void
    {
        $input = $this->cashInput();
        $action = app(CompleteOrder::class);

        $first = $action->handle($this->cashier, $this->shift, $input);
        $second = $action->handle($this->cashier, $this->shift, $input);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_insufficient_stock_rolls_back_everything(): void
    {
        $this->pisangGoreng->update(['track_stock' => true, 'stock_qty' => 0]);

        try {
            $this->complete();
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Stok Pisang Goreng tidak cukup', $e->errors()['cart'][0]);
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_tracked_stock_is_deducted_with_movement(): void
    {
        $this->pisangGoreng->update(['track_stock' => true, 'stock_qty' => 5]);

        $order = $this->complete();

        $this->assertSame(4, $this->pisangGoreng->fresh()->stock_qty);
        $movement = StockMovement::firstOrFail();
        $this->assertSame('sale', $movement->type->value);
        $this->assertSame($order->id, $movement->order_id);
        $this->assertEquals(-1, $movement->qty);
    }

    public function test_negative_stock_allowed_only_by_setting(): void
    {
        $this->pisangGoreng->update(['track_stock' => true, 'stock_qty' => 0]);
        app(SettingService::class)->set('allow_negative_stock', true);

        $this->complete();

        $this->assertSame(-1, $this->pisangGoreng->fresh()->stock_qty);
    }

    public function test_order_numbers_are_sequential_per_business_date(): void
    {
        $a = $this->complete();
        $b = $this->complete();

        $this->assertSame('A-0001', $a->number);
        $this->assertSame('A-0002', $b->number);

        $this->travel(1)->days();
        $c = $this->complete();
        $this->assertSame('A-0001', $c->number);
    }

    public function test_cart_cannot_be_empty(): void
    {
        $this->expectException(ValidationException::class);
        $this->complete(['lines' => []]);
    }

    public function test_inactive_product_cannot_be_ordered(): void
    {
        $this->esKopi->update(['is_active' => false]);

        $this->expectException(ValidationException::class);
        $this->complete();
    }

    public function test_cashier_discount_requires_approver(): void
    {
        try {
            $this->complete(['discount_type' => 'amount', 'discount_value' => 3000]);
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('discount', $e->errors());
        }

        $admin = $this->admin();
        $order = $this->complete(['discount_type' => 'amount', 'discount_value' => 3000, 'discount_approved_by' => $admin->id]);

        $this->assertSame(3000, $order->discount);
        $this->assertSame($admin->id, $order->discount_approved_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.discount', 'approved_by' => $admin->id]);
    }

    public function test_cashier_cannot_use_another_cashier_as_approver(): void
    {
        $other = User::factory()->cashier()->create();

        $this->expectException(ValidationException::class);
        $this->complete(['discount_type' => 'amount', 'discount_value' => 3000, 'discount_approved_by' => $other->id]);
    }

    public function test_cannot_transact_on_closed_shift(): void
    {
        $this->shift->update(['status' => 'closed']);

        $this->expectException(ValidationException::class);
        $this->complete();
    }

    public function test_open_order_can_be_saved_resumed_and_paid(): void
    {
        $input = $this->cashInput();
        $open = app(SaveOpenOrder::class)->handle($this->cashier, $this->shift, $input);

        $this->assertSame('open', $open->status->value);
        $this->assertDatabaseCount('payments', 0);

        $paid = app(CompleteOrder::class)->handle($this->cashier, $this->shift, $input);

        $this->assertSame($open->id, $paid->id);
        $this->assertSame('paid', $paid->status->value);
        $this->assertDatabaseCount('orders', 1);
        $this->assertCount(3, $paid->items);
    }

    public function test_saving_open_order_twice_updates_instead_of_duplicating(): void
    {
        $input = $this->cashInput();
        $action = app(SaveOpenOrder::class);
        $action->handle($this->cashier, $this->shift, $input);

        $input['lines'] = [['product_id' => $this->esKopi->id, 'qty' => 1]];
        $order = $action->handle($this->cashier, $this->shift, $input);

        $this->assertDatabaseCount('orders', 1);
        $this->assertCount(1, $order->items);
        $this->assertSame(19800, $order->total);
    }

    public function test_open_order_can_be_discarded_without_touching_cash_or_stock(): void
    {
        $open = app(SaveOpenOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        $order = app(DiscardOpenOrder::class)->handle($open, $this->cashier);

        $this->assertSame('void', $order->status->value);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(200000, $this->shift->expectedCash());
    }

    public function test_payment_row_count_is_one_per_order(): void
    {
        $this->complete();

        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Order::count());
    }
}

<?php

namespace Tests\Feature\Pos;

use App\Actions\Shifts\CloseShift;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\VariantGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'shift_id' => $this->shift->id,
            'lines' => $this->standardLines(),
            'order_type' => 'take_away',
            'idempotency_key' => (string) Str::uuid(),
            'method' => 'cash',
            'paid_amount' => 100000,
        ], $override);
    }

    public function test_queued_offline_order_is_created_on_sync(): void
    {
        $response = $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $this->payload());

        $response->assertOk();
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('paid', Order::first()->status->value);
    }

    public function test_same_idempotency_key_is_not_duplicated(): void
    {
        $payload = $this->payload();

        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $payload)->assertOk();
        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $payload)->assertOk();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_mismatched_client_estimate_is_flagged_in_audit_log(): void
    {
        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $this->payload([
            'client_estimated_total' => 999,
        ]))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.offline_adjusted',
            'subject_type' => 'order',
        ]);
        $this->assertSame(999, AuditLog::where('action', 'order.offline_adjusted')->first()->old_values['client_estimated_total']);
    }

    public function test_matching_client_estimate_is_not_flagged(): void
    {
        $order = $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $payload = $this->payload())->json();

        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $this->payload([
            'idempotency_key' => (string) Str::uuid(),
            'client_estimated_total' => $order['total'],
        ]))->assertOk();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'order.offline_adjusted']);
    }

    public function test_sync_against_closed_shift_fails_without_creating_order(): void
    {
        app(CloseShift::class)->handle($this->shift, $this->cashier, $this->shift->opening_cash, '');

        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $this->payload())
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_sync_insufficient_stock_rolls_back_without_duplicate(): void
    {
        $this->pisangGoreng->update(['track_stock' => true, 'stock_qty' => 0]);

        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $this->payload())
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_sync_with_selected_variant_option_prices_with_delta(): void
    {
        $group = VariantGroup::create(['product_id' => $this->esKopi->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);
        $large = $group->options()->create(['name' => 'Large', 'price_delta' => 5000]);

        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $this->payload([
            'lines' => [
                ['product_id' => $this->esKopi->id, 'option_ids' => [$large->id], 'qty' => 1],
            ],
        ]))->assertOk();

        $this->assertSame(25300, Order::firstOrFail()->total);
    }

    public function test_sync_with_missing_required_variant_option_is_rejected(): void
    {
        VariantGroup::create(['product_id' => $this->esKopi->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);

        $this->actingAs($this->cashier)->postJson('/pos/offline-sync', $this->payload([
            'lines' => [
                ['product_id' => $this->esKopi->id, 'option_ids' => [], 'qty' => 1],
            ],
        ]))->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_cannot_sync(): void
    {
        $this->postJson('/pos/offline-sync', $this->payload())->assertUnauthorized();
    }
}

<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\Cart;
use App\Models\Order;
use App\Models\User;
use App\Models\VariantGroup;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();
    }

    private function cart()
    {
        return Livewire::actingAs($this->cashier)->test(Cart::class);
    }

    public function test_cashier_without_open_shift_is_redirected_to_open_shift(): void
    {
        $other = User::factory()->cashier()->create();

        $this->actingAs($other)->get('/pos')->assertRedirect('/shift/open');
        $this->actingAs($other)->get('/shift/close')->assertRedirect('/shift/open');
        $this->actingAs($this->cashier)->get('/pos')->assertOk();
    }

    public function test_cart_merges_identical_items_and_shows_server_totals(): void
    {
        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->dispatch('cart-add', productId: $this->nasiGoreng->id, optionIds: [])
            ->dispatch('cart-add', productId: $this->pisangGoreng->id, optionIds: [])
            ->assertCount('lines', 3)
            ->assertSee('Rp 73.000')
            ->assertSee('Rp 7.300')
            ->assertSee('Rp 80.300');
    }

    public function test_different_options_are_separate_lines(): void
    {
        $group = VariantGroup::create(['product_id' => $this->esKopi->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);
        $regular = $group->options()->create(['name' => 'Regular', 'price_delta' => 0]);
        $large = $group->options()->create(['name' => 'Large', 'price_delta' => 5000]);

        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [$regular->id])
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [$large->id])
            ->assertCount('lines', 2)
            ->assertSee('Rp 18.000')
            ->assertSee('Rp 23.000');
    }

    public function test_invalid_option_is_not_added(): void
    {
        VariantGroup::create(['product_id' => $this->esKopi->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);

        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->assertCount('lines', 0)
            ->assertDispatched('toast');
    }

    public function test_quantity_controls_and_removal(): void
    {
        $component = $this->cart()->dispatch('cart-add', productId: $this->esKopi->id, optionIds: []);
        $key = $component->get('lines')[0]['key'];

        $component->call('increment', $key)->assertSet('lines.0.qty', 2)
            ->call('decrement', $key)->assertSet('lines.0.qty', 1)
            ->call('decrement', $key)->assertCount('lines', 0)
            ->assertSee('Belum ada pesanan');
    }

    public function test_pay_cash_completes_order_and_shows_change(): void
    {
        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->dispatch('cart-add', productId: $this->nasiGoreng->id, optionIds: [])
            ->dispatch('cart-add', productId: $this->pisangGoreng->id, optionIds: [])
            ->call('openPay')
            ->set('paid_amount', 100000)
            ->call('complete')
            ->assertHasNoErrors()
            ->assertSee('Pembayaran berhasil')
            ->assertSee('Rp 19.700');

        $order = Order::firstOrFail();
        $this->assertSame(80300, $order->total);
        $this->assertSame('paid', $order->status->value);
    }

    public function test_cash_below_total_is_rejected_by_server_even_if_button_is_bypassed(): void
    {
        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->call('openPay')
            ->set('paid_amount', 1000)
            ->call('complete')
            ->assertHasErrors('paid_amount');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_double_click_on_complete_creates_single_order(): void
    {
        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->call('openPay')
            ->set('paid_amount', 50000)
            ->call('complete')
            ->set('paid_amount', 50000)
            ->call('complete');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_insufficient_stock_keeps_cart_and_writes_nothing(): void
    {
        $this->pisangGoreng->update(['track_stock' => true, 'stock_qty' => 0]);
        app(SettingService::class)->set('allow_negative_stock', false);

        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->dispatch('cart-add', productId: $this->pisangGoreng->id, optionIds: [])
            ->call('openPay')
            ->set('paid_amount', 100000)
            ->call('complete')
            ->assertHasErrors('cart')
            ->assertCount('lines', 2)
            ->assertSet('completedOrderId', null);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_client_cannot_tamper_with_locked_discount_state(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->cart()->set('discount_value', 99999);
    }

    public function test_cashier_discount_needs_valid_approver_pin(): void
    {
        $this->admin('246813');

        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->call('openDiscount')
            ->set('draft_discount_value', 3000)
            ->set('approver_pin', '000111')
            ->call('applyDiscount')
            ->assertHasErrors('approver_pin')
            ->assertSet('discount_value', 0)
            ->set('approver_pin', '246813')
            ->call('applyDiscount')
            ->assertHasNoErrors()
            ->assertSet('discount_value', 3000)
            ->assertSee('Rp 3.000')
            ->assertSee('Rp 16.500');
    }

    public function test_save_open_then_resume_and_pay(): void
    {
        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->call('saveOpen')
            ->assertCount('lines', 0);

        $open = Order::firstOrFail();
        $this->assertSame('open', $open->status->value);

        $this->actingAs($this->cashier)->get('/pos?resume='.$open->id)->assertOk();

        Livewire::actingAs($this->cashier)
            ->withQueryParams(['resume' => $open->id])
            ->test(Cart::class)
            ->assertCount('lines', 1)
            ->call('openPay')
            ->set('paid_amount', 50000)
            ->call('complete');

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('paid', $open->fresh()->status->value);
    }

    public function test_pay_dialog_offers_only_enabled_methods(): void
    {
        app(SettingService::class)->set('method_card', false);
        app(SettingService::class)->set('method_qris', false);

        $this->cart()
            ->dispatch('cart-add', productId: $this->esKopi->id, optionIds: [])
            ->call('openPay')
            ->assertSee('Tunai')
            ->assertDontSee('Debit/Transfer')
            ->assertDontSee('QRIS');
    }
}

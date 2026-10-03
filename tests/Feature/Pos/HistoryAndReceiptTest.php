<?php

namespace Tests\Feature\Pos;

use App\Actions\Orders\CompleteOrder;
use App\Livewire\Pos\History;
use App\Models\Order;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class HistoryAndReceiptTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();
        app(SettingService::class)->set('outlet_name', 'Kedai Uji');
        app(SettingService::class)->set('receipt_footer', 'Terima kasih');
        $this->order = app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());
    }

    public function test_receipt_shows_required_fields(): void
    {
        $this->actingAs($this->cashier)->get('/receipts/'.$this->order->id)
            ->assertOk()
            ->assertSee('Kedai Uji')
            ->assertSee($this->order->number)
            ->assertSee('WITA')
            ->assertSee('Es Kopi Susu Gula Aren')
            ->assertSee('Rp 73.000')
            ->assertSee('PB1 10%')
            ->assertSee('Rp 7.300')
            ->assertSee('Rp 80.300')
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 19.700')
            ->assertSee('Terima kasih');
    }

    public function test_receipt_access_rules(): void
    {
        $this->actingAs(User::factory()->cashier()->create())->get('/receipts/'.$this->order->id)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/receipts/'.$this->order->id)->assertOk();
        $this->actingAs(User::factory()->owner()->create())->get('/receipts/'.$this->order->id)->assertOk();
        auth()->logout();
        $this->get('/receipts/'.$this->order->id)->assertRedirect('/login');
    }

    public function test_history_lists_todays_own_orders_for_cashier(): void
    {
        $other = User::factory()->cashier()->create();

        Livewire::actingAs($this->cashier)->test(History::class)->assertSee($this->order->number);
        Livewire::actingAs($other)->test(History::class)->assertDontSee($this->order->number)->assertSee('Belum ada transaksi hari ini.');
    }

    public function test_cashier_void_needs_admin_pin_and_reason(): void
    {
        $this->admin('246813');

        Livewire::actingAs($this->cashier)->test(History::class)
            ->call('startVoid', $this->order->id)
            ->set('reason', 'x')
            ->call('confirmVoid')
            ->assertHasErrors('reason')
            ->set('reason', 'Salah input pesanan')
            ->set('approver_pin', '000111')
            ->call('confirmVoid')
            ->assertHasErrors('approver_pin');

        $this->assertSame('paid', $this->order->fresh()->status->value);

        Livewire::actingAs($this->cashier)->test(History::class)
            ->call('startVoid', $this->order->id)
            ->set('reason', 'Salah input pesanan')
            ->set('approver_pin', '246813')
            ->call('confirmVoid')
            ->assertHasNoErrors()
            ->assertSet('voidingId', null);

        $this->assertSame('void', $this->order->fresh()->status->value);
    }

    public function test_cashier_cannot_start_void_on_another_cashiers_order(): void
    {
        Livewire::actingAs(User::factory()->cashier()->create())->test(History::class)
            ->call('startVoid', $this->order->id)
            ->assertForbidden();
    }

    public function test_admin_void_needs_no_pin(): void
    {
        Livewire::actingAs($this->admin())->test(History::class)
            ->call('startVoid', $this->order->id)
            ->set('reason', 'Pelanggan membatalkan')
            ->call('confirmVoid')
            ->assertHasNoErrors();

        $this->assertSame('void', $this->order->fresh()->status->value);
    }
}

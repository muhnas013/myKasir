<?php

namespace Tests\Feature\Shift;

use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\SaveOpenOrder;
use App\Livewire\Shift\Close;
use App\Livewire\Shift\Open;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class ShiftPagesTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    public function test_cashier_opens_shift_with_opening_cash(): void
    {
        $cashier = User::factory()->cashier()->create();

        Livewire::actingAs($cashier)->test(Open::class)
            ->set('opening_cash', 150000)
            ->call('save')
            ->assertRedirect('/pos');

        $this->assertDatabaseHas('shifts', ['user_id' => $cashier->id, 'status' => 'open', 'opening_cash' => 150000]);
    }

    public function test_negative_opening_cash_shows_error(): void
    {
        Livewire::actingAs(User::factory()->cashier()->create())->test(Open::class)
            ->set('opening_cash', -5)
            ->call('save')
            ->assertHasErrors('opening_cash');
    }

    public function test_close_page_flow_with_difference_note(): void
    {
        $this->setUpPos();
        app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        Livewire::actingAs($this->cashier)->test(Close::class)
            ->set('counted_cash', 280000)
            ->call('save')
            ->assertHasErrors('closing_note')
            ->set('closing_note', 'Kurang kembalian')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Rp 280.300')
            ->assertSee('-Rp 300');

        $this->assertSame('closed', Shift::first()->status->value);
    }

    public function test_closing_with_open_orders_shows_error(): void
    {
        $this->setUpPos();
        app(SaveOpenOrder::class)->handle($this->cashier, $this->shift, $this->cashInput());

        Livewire::actingAs($this->cashier)->test(Close::class)
            ->set('counted_cash', 200000)
            ->call('save')
            ->assertHasErrors('shift');

        $this->assertSame('open', $this->shift->fresh()->status->value);
    }

    public function test_after_closing_pos_redirects_back_to_open_shift(): void
    {
        $this->setUpPos();
        Livewire::actingAs($this->cashier)->test(Close::class)->set('counted_cash', 200000)->call('save');

        $this->actingAs($this->cashier)->get('/pos')->assertRedirect('/shift/open');
    }
}

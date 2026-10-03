<?php

namespace Tests\Feature\Stock;

use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\VoidOrder;
use App\Enums\StockMovementType;
use App\Livewire\Pos\Grid;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\SetsUpPos;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase, SetsUpPos;

    protected Ingredient $espresso;

    protected Ingredient $susu;

    protected Ingredient $gulaAren;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPos();

        $this->espresso = Ingredient::factory()->create(['name' => 'Espresso', 'unit' => 'g', 'stock_qty' => 1000, 'min_qty' => 100, 'avg_cost' => 500]);
        $this->susu = Ingredient::factory()->create(['name' => 'Susu', 'unit' => 'ml', 'stock_qty' => 5000, 'min_qty' => 500, 'avg_cost' => 200]);
        $this->gulaAren = Ingredient::factory()->create(['name' => 'Gula Aren', 'unit' => 'ml', 'stock_qty' => 2000, 'min_qty' => 200, 'avg_cost' => 300]);

        Recipe::create(['ingredient_id' => $this->espresso->id, 'product_id' => $this->esKopi->id, 'qty' => 18]);
        Recipe::create(['ingredient_id' => $this->susu->id, 'product_id' => $this->esKopi->id, 'qty' => 120]);
        Recipe::create(['ingredient_id' => $this->gulaAren->id, 'product_id' => $this->esKopi->id, 'qty' => 25]);
    }

    private function completeOneEsKopi(): Order
    {
        return app(CompleteOrder::class)->handle($this->cashier, $this->shift, $this->cashInput([
            'lines' => [['product_id' => $this->esKopi->id, 'qty' => 1]],
            'paid_amount' => 20000,
        ]));
    }

    public function test_selling_product_with_recipe_deducts_each_ingredient_and_logs_sale_movements(): void
    {
        $order = $this->completeOneEsKopi();

        $movements = StockMovement::where('order_id', $order->id)->where('type', StockMovementType::Sale)->get();
        $this->assertCount(3, $movements);
        $this->assertTrue($movements->every(fn ($m) => $m->ingredient_id !== null));

        $this->assertSame('982.000', $this->espresso->fresh()->stock_qty);
        $this->assertSame('4880.000', $this->susu->fresh()->stock_qty);
        $this->assertSame('1975.000', $this->gulaAren->fresh()->stock_qty);
    }

    public function test_insufficient_ingredient_stock_blocks_sale(): void
    {
        $this->espresso->update(['stock_qty' => 5]); // kurang dari 18 g yang dibutuhkan

        try {
            $this->completeOneEsKopi();
            $this->fail('Seharusnya melempar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cart', $e->errors());
        }

        $this->assertSame('5.000', $this->espresso->fresh()->stock_qty);
        $this->assertSame(0, StockMovement::where('type', StockMovementType::Sale)->count());
    }

    public function test_product_without_enough_recipe_stock_shows_habis_in_grid(): void
    {
        $this->espresso->update(['stock_qty' => 5]);

        $this->assertFalse(app(StockService::class)->isSellable($this->esKopi->fresh('recipes.ingredient')));

        Livewire::actingAs($this->cashier)
            ->test(Grid::class)
            ->assertSee('Habis');
    }

    public function test_void_returns_ingredient_stock(): void
    {
        $order = $this->completeOneEsKopi();

        $admin = $this->admin();
        app(VoidOrder::class)->handle($order, $this->cashier, 'Pesanan salah input', $admin->id);

        $this->assertSame('1000.000', $this->espresso->fresh()->stock_qty);
        $this->assertSame('5000.000', $this->susu->fresh()->stock_qty);
        $this->assertSame('2000.000', $this->gulaAren->fresh()->stock_qty);

        $this->assertSame(3, StockMovement::where('order_id', $order->id)->where('type', StockMovementType::VoidReturn)->count());
    }

    public function test_stock_in_recalculates_weighted_average_avg_cost(): void
    {
        $susu = Ingredient::factory()->create(['stock_qty' => 2000, 'avg_cost' => 17000]);

        app(StockService::class)->receivePurchase($susu, '5000', 90000, $this->cashier);

        $susu->refresh();
        $this->assertSame(17714, $susu->avg_cost);
        $this->assertSame('7000.000', $susu->stock_qty);

        $movement = StockMovement::where('ingredient_id', $susu->id)->where('type', StockMovementType::Purchase)->firstOrFail();
        $this->assertSame('5000.000', $movement->qty);
        $this->assertSame(90000, $movement->total_cost);
    }

    public function test_opname_without_reason_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(StockService::class)->adjustStock($this->espresso, '950', '', $this->cashier);
    }

    public function test_opname_with_reason_records_adjustment(): void
    {
        app(StockService::class)->adjustStock($this->espresso, '950', 'Opname bulanan', $this->cashier);

        $this->espresso->refresh();
        $this->assertSame('950.000', $this->espresso->stock_qty);

        $movement = StockMovement::where('ingredient_id', $this->espresso->id)->where('type', StockMovementType::Adjustment)->firstOrFail();
        $this->assertSame('-50.000', $movement->qty);
        $this->assertSame('Opname bulanan', $movement->note);
    }

    public function test_ingredients_at_or_below_min_qty_are_low_stock(): void
    {
        $this->espresso->update(['stock_qty' => 100, 'min_qty' => 100]);

        $this->assertTrue(Ingredient::lowStock()->whereKey($this->espresso->id)->exists());
    }
}

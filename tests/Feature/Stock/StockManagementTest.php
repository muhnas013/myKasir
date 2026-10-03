<?php

namespace Tests\Feature\Stock;

use App\Livewire\Stock\IngredientTable;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_admin_can_open_stock_page(): void
    {
        $this->actingAs(User::factory()->owner()->create())->get('/stock')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/stock')->assertOk();
    }

    public function test_cashier_gets_403_on_stock_page(): void
    {
        $this->actingAs(User::factory()->cashier()->create())->get('/stock')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/stock')->assertRedirect('/login');
    }

    public function test_admin_can_create_ingredient(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(IngredientTable::class)
            ->call('create')
            ->set('name', 'Espresso')
            ->set('unit', 'g')
            ->set('min_qty', 200)
            ->call('save')
            ->assertHasNoErrors();

        $ingredient = Ingredient::where('name', 'Espresso')->firstOrFail();
        $this->assertSame('g', $ingredient->unit);
        $this->assertSame('200.000', $ingredient->min_qty);
        $this->assertSame(0, $ingredient->avg_cost);
        $this->assertSame('0.000', $ingredient->stock_qty);
    }

    public function test_ingredient_name_must_be_unique(): void
    {
        Ingredient::factory()->create(['name' => 'Gula Aren']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(IngredientTable::class)
            ->call('create')
            ->set('name', 'Gula Aren')
            ->set('unit', 'ml')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_deleting_ingredient_used_in_recipe_is_blocked(): void
    {
        $ingredient = Ingredient::factory()->create();
        $product = Product::factory()->create();
        Recipe::create(['ingredient_id' => $ingredient->id, 'product_id' => $product->id, 'qty' => 10]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(IngredientTable::class)
            ->call('delete', $ingredient->id);

        $this->assertNotNull($ingredient->fresh());
    }

    public function test_low_stock_ingredients_are_listed(): void
    {
        $low = Ingredient::factory()->create(['stock_qty' => 50, 'min_qty' => 100]);
        $healthy = Ingredient::factory()->create(['stock_qty' => 500, 'min_qty' => 100]);

        $lowStock = Livewire::actingAs(User::factory()->admin()->create())
            ->test(IngredientTable::class)
            ->viewData('lowStock');

        $this->assertTrue($lowStock->contains('id', $low->id));
        $this->assertFalse($lowStock->contains('id', $healthy->id));
    }
}

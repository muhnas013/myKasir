<?php

namespace Tests\Feature\Stock;

use App\Actions\Stock\SaveRecipe;
use App\Livewire\Stock\RecipeEditor;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecipeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_price_is_used_as_fallback_hpp_when_no_recipe(): void
    {
        $product = Product::factory()->create(['cost_price' => 7000]);

        $this->assertSame(7000, app(StockService::class)->porsiCost($product));
    }

    public function test_recipe_cost_takes_precedence_over_cost_price(): void
    {
        $product = Product::factory()->create(['cost_price' => 7000]);
        $ingredient = Ingredient::factory()->create(['avg_cost' => 1000]);
        Recipe::create(['ingredient_id' => $ingredient->id, 'product_id' => $product->id, 'qty' => 18]);

        $product->load('recipes');

        // 18 * 1000 / 1000 = 18, bukan 7000 — resep lebih presisi dan menang.
        $this->assertSame(18, app(StockService::class)->porsiCost($product));
    }

    public function test_save_recipe_syncs_base_and_option_lines(): void
    {
        $product = Product::factory()->create();
        $group = VariantGroup::create(['product_id' => $product->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);
        $option = VariantOption::create(['variant_group_id' => $group->id, 'name' => 'Large', 'price_delta' => 5000]);

        $espresso = Ingredient::factory()->create(['name' => 'Espresso']);
        $susu = Ingredient::factory()->create(['name' => 'Susu']);

        app(SaveRecipe::class)->handle(
            $product->fresh('variantGroups.options'),
            [['ingredient_id' => $espresso->id, 'qty' => 18]],
            [$option->id => [['ingredient_id' => $susu->id, 'qty' => 50]]],
        );

        $this->assertSame(1, $product->recipes()->count());
        $this->assertSame('18.000', $product->recipes()->firstOrFail()->qty);
        $this->assertSame(1, $option->recipes()->count());
        $this->assertSame('50.000', $option->recipes()->firstOrFail()->qty);
    }

    public function test_save_recipe_removes_lines_not_resent(): void
    {
        $product = Product::factory()->create();
        $a = Ingredient::factory()->create();
        $b = Ingredient::factory()->create();

        Recipe::create(['ingredient_id' => $a->id, 'product_id' => $product->id, 'qty' => 10]);
        Recipe::create(['ingredient_id' => $b->id, 'product_id' => $product->id, 'qty' => 20]);

        app(SaveRecipe::class)->handle($product->fresh('variantGroups.options'), [
            ['ingredient_id' => $b->id, 'qty' => 25],
        ], []);

        $this->assertSame(1, $product->recipes()->count());
        $this->assertSame($b->id, $product->recipes()->firstOrFail()->ingredient_id);
        $this->assertSame('25.000', $product->recipes()->firstOrFail()->qty);
    }

    public function test_porsi_cost_includes_base_and_selected_option(): void
    {
        $product = Product::factory()->create();
        $group = VariantGroup::create(['product_id' => $product->id, 'name' => 'Ukuran', 'is_required' => false, 'max_select' => 1]);
        $option = VariantOption::create(['variant_group_id' => $group->id, 'name' => 'Large', 'price_delta' => 5000]);

        $espresso = Ingredient::factory()->create(['avg_cost' => 1000]); // Rp 1.000 / 1000 satuan
        $susu = Ingredient::factory()->create(['avg_cost' => 2000]);

        Recipe::create(['ingredient_id' => $espresso->id, 'product_id' => $product->id, 'qty' => 18]);
        Recipe::create(['ingredient_id' => $susu->id, 'variant_option_id' => $option->id, 'qty' => 50]);

        $product->load('recipes', 'variantGroups.options.recipes');

        $costWithoutOption = app(StockService::class)->porsiCost($product, []);
        $costWithOption = app(StockService::class)->porsiCost($product, [$option->id]);

        // 18 * 1000 / 1000 = 18
        $this->assertSame(18, $costWithoutOption);
        // 18 + (50 * 2000 / 1000) = 18 + 100 = 118
        $this->assertSame(118, $costWithOption);
    }

    public function test_admin_can_save_recipe_via_livewire(): void
    {
        $product = Product::factory()->create();
        $ingredient = Ingredient::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(RecipeEditor::class)
            ->set('productId', (string) $product->id)
            ->call('selectProduct')
            ->call('addBaseLine')
            ->set('baseLines.0.ingredient_id', (string) $ingredient->id)
            ->set('baseLines.0.qty', 15)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $product->recipes()->count());
    }
}

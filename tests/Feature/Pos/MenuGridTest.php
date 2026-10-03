<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\Grid;
use App\Models\Product;
use App\Models\User;
use App\Models\VariantGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuGridTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_product_is_not_shown_in_grid(): void
    {
        Product::factory()->create(['name' => 'Es Kopi Aktif']);
        Product::factory()->inactive()->create(['name' => 'Menu Tersembunyi']);

        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Grid::class)
            ->assertSee('Es Kopi Aktif')
            ->assertDontSee('Menu Tersembunyi');
    }

    public function test_soft_deleted_product_is_not_shown_in_grid(): void
    {
        Product::factory()->create(['name' => 'Menu Dihapus'])->delete();

        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Grid::class)
            ->assertDontSee('Menu Dihapus');
    }

    public function test_inactive_product_cannot_be_selected_by_id(): void
    {
        $product = Product::factory()->inactive()->create();

        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Grid::class)
            ->call('selectProduct', $product->id)
            ->assertNotFound()
            ->assertNotDispatched('cart-add');
    }

    public function test_product_without_variants_is_added_directly(): void
    {
        $product = Product::factory()->create();

        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Grid::class)
            ->call('selectProduct', $product->id)
            ->assertDispatched('cart-add', productId: $product->id, optionIds: [])
            ->assertSet('selectingProductId', null);
    }

    public function test_required_variant_opens_dialog_and_blocks_adding_without_choice(): void
    {
        [$product, $group, $large] = $this->productWithRequiredGroup();

        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Grid::class)
            ->call('selectProduct', $product->id)
            ->assertSet('selectingProductId', $product->id)
            ->assertNotDispatched('cart-add')
            ->call('confirmOptions')
            ->assertHasErrors('selected.'.$group->id)
            ->assertNotDispatched('cart-add')
            ->set('selected.'.$group->id, (string) $large->id)
            ->call('confirmOptions')
            ->assertHasNoErrors()
            ->assertDispatched('cart-add', productId: $product->id, optionIds: [$large->id])
            ->assertSet('selectingProductId', null);
    }

    public function test_option_from_another_product_is_rejected(): void
    {
        [$product, $group] = $this->productWithRequiredGroup();
        [, , $foreignOption] = $this->productWithRequiredGroup();

        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Grid::class)
            ->call('selectProduct', $product->id)
            ->set('selected.'.$group->id, (string) $foreignOption->id)
            ->call('confirmOptions')
            ->assertHasErrors('selected.'.$group->id)
            ->assertNotDispatched('cart-add');
    }

    public function test_grid_shows_empty_state_without_active_products(): void
    {
        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Grid::class)
            ->assertSee('Belum ada menu aktif.');
    }

    public function test_all_active_roles_can_open_pos_but_inactive_user_cannot_transact(): void
    {
        $this->actingAs(User::factory()->cashier()->create())->get('/pos')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/pos')->assertOk();
        $this->actingAs(User::factory()->owner()->create())->get('/pos')->assertOk();

        $this->assertFalse(User::factory()->cashier()->make(['is_active' => false])->can('pos.transact'));
    }

    private function productWithRequiredGroup(): array
    {
        $product = Product::factory()->create();
        $group = VariantGroup::create(['product_id' => $product->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1]);
        $large = $group->options()->create(['name' => 'Large', 'price_delta' => 5000]);

        return [$product, $group, $large];
    }
}

<?php

namespace Tests\Feature\Menu;

use App\Livewire\Menu\Categories;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Categories::class)
            ->call('create')
            ->set('name', 'Kopi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Kopi']);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Kopi']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Categories::class)
            ->call('create')
            ->set('name', 'Kopi')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Categories::class)
            ->call('delete', $product->category_id);

        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $category = Category::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Categories::class)
            ->call('delete', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cashier_cannot_use_category_component(): void
    {
        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(Categories::class)
            ->assertForbidden();
    }

    public function test_empty_state_is_shown_without_categories(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Categories::class)
            ->assertSee('Belum ada kategori. Tambah kategori pertama.');
    }
}

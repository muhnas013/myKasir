<?php

namespace Tests\Feature\Menu;

use App\Actions\Menu\SaveProduct;
use App\Livewire\Menu\ProductTable;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_variants(): void
    {
        $category = Category::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->call('create')
            ->set('category_id', (string) $category->id)
            ->set('sku', 'KP-001')
            ->set('name', 'Es Kopi Susu Gula Aren')
            ->set('price', 18000)
            ->call('addGroup')
            ->set('groups.0.name', 'Ukuran')
            ->set('groups.0.is_required', true)
            ->set('groups.0.options.0.name', 'Large')
            ->set('groups.0.options.0.price_delta', 5000)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('sku', 'KP-001')->firstOrFail();
        $this->assertSame(18000, $product->price);
        $group = $product->variantGroups()->firstOrFail();
        $this->assertTrue($group->is_required);
        $this->assertSame(5000, $group->options()->firstOrFail()->price_delta);
    }

    public function test_admin_can_upload_product_photo(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->call('create')
            ->set('category_id', (string) $category->id)
            ->set('sku', 'KP-002')
            ->set('name', 'Teh Tarik')
            ->set('price', 15000)
            ->set('photo', UploadedFile::fake()->create('teh-tarik.jpg', 50, 'image/jpeg'))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('sku', 'KP-002')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertStringContainsString($product->image_path, $product->photo_url);
    }

    public function test_editing_product_without_new_photo_keeps_existing_one(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['image_path' => 'products/existing.jpg']);
        Storage::disk('public')->put('products/existing.jpg', 'fake-content');

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->call('edit', $product->id)
            ->set('name', 'Nama Diubah')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('products/existing.jpg', $product->fresh()->image_path);
    }

    public function test_admin_can_set_cost_price(): void
    {
        $category = Category::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->call('create')
            ->set('category_id', (string) $category->id)
            ->set('sku', 'TH-001')
            ->set('name', 'Teh Es')
            ->set('price', 8000)
            ->set('cost_price', 3000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['sku' => 'TH-001', 'price' => 8000, 'cost_price' => 3000]);
    }

    public function test_product_validation_rejects_bad_input(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->call('create')
            ->set('price', -1)
            ->call('save')
            ->assertHasErrors(['category_id', 'sku', 'name', 'price']);
    }

    public function test_sku_must_be_unique(): void
    {
        $existing = Product::factory()->create(['sku' => 'KP-001']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->call('create')
            ->set('category_id', (string) $existing->category_id)
            ->set('sku', 'KP-001')
            ->set('name', 'Lain')
            ->set('price', 1000)
            ->call('save')
            ->assertHasErrors('sku');
    }

    public function test_price_change_is_audited_with_old_and_new_price(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['price' => 18000]);

        app(SaveProduct::class)->handle($product, $this->payload($product, ['price' => 20000]), $admin);

        $log = AuditLog::where('action', 'product.price_changed')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($product->id, $log->subject_id);
        $this->assertSame(['price' => 18000], $log->old_values);
        $this->assertSame(['price' => 20000], $log->new_values);
    }

    public function test_no_audit_when_price_unchanged(): void
    {
        $product = Product::factory()->create(['price' => 18000]);

        app(SaveProduct::class)->handle($product, $this->payload($product, ['name' => 'Nama Baru']), User::factory()->admin()->create());

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_variant_sync_keeps_ids_and_removes_missing_ones(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $action = app(SaveProduct::class);

        $action->handle($product, $this->payload($product, ['variant_groups' => [[
            'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1,
            'options' => [
                ['name' => 'Regular', 'price_delta' => 0],
                ['name' => 'Large', 'price_delta' => 5000],
            ],
        ]]]), $admin);

        $group = $product->variantGroups()->with('options')->firstOrFail();
        [$regular, $large] = $group->options->all();

        $action->handle($product, $this->payload($product, ['variant_groups' => [[
            'id' => $group->id, 'name' => 'Ukuran', 'is_required' => true, 'max_select' => 1,
            'options' => [['id' => $regular->id, 'name' => 'Reguler', 'price_delta' => 0]],
        ]]]), $admin);

        $this->assertSame('Reguler', $regular->fresh()->name);
        $this->assertDatabaseMissing('variant_options', ['id' => $large->id]);
        $this->assertDatabaseHas('variant_groups', ['id' => $group->id]);
    }

    public function test_deleting_product_is_soft_delete(): void
    {
        $product = Product::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->call('delete', $product->id);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cashier_cannot_use_product_component(): void
    {
        Livewire::actingAs(User::factory()->cashier()->create())
            ->test(ProductTable::class)
            ->assertForbidden();
    }

    public function test_empty_state_is_shown_without_products(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ProductTable::class)
            ->assertSee('Belum ada menu. Tambah menu pertama.');
    }

    private function payload(Product $product, array $override = []): array
    {
        return array_merge([
            'category_id' => $product->category_id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'is_active' => $product->is_active,
            'track_stock' => $product->track_stock,
        ], $override);
    }
}

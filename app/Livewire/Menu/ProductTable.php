<?php

namespace App\Livewire\Menu;

use App\Actions\Menu\DeleteProduct;
use App\Actions\Menu\SaveProduct;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductTable extends Component
{
    use WithFileUploads;

    public string $search = '';

    public string $categoryFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $category_id = '';

    public string $sku = '';

    public string $name = '';

    public int|string $price = 0;

    public int|string $cost_price = 0;

    public bool $is_active = true;

    public bool $track_stock = false;

    public int|string $stock_qty = 0;

    public $photo = null;

    public ?string $image_path = null;

    /** @var list<array{id: ?int, name: string, is_required: bool, max_select: int|string, options: list<array{id: ?int, name: string, price_delta: int|string}>}> */
    public array $groups = [];

    public function mount(): void
    {
        Gate::authorize('menu.manage');
    }

    public function render()
    {
        $products = Product::query()
            ->with('category')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('sku', 'like', '%'.$this->search.'%')))
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->orderBy('name')
            ->get();

        return view('livewire.menu.product-table', [
            'products' => $products,
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): void
    {
        Gate::authorize('menu.manage');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $productId): void
    {
        Gate::authorize('menu.manage');
        $product = Product::with('variantGroups.options')->findOrFail($productId);

        $this->resetForm();
        $this->editingId = $product->id;
        $this->category_id = (string) $product->category_id;
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->price = $product->price;
        $this->cost_price = $product->cost_price;
        $this->is_active = $product->is_active;
        $this->track_stock = $product->track_stock;
        $this->stock_qty = $product->stock_qty;
        $this->image_path = $product->image_path;
        $this->groups = $product->variantGroups->map(fn ($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'is_required' => $group->is_required,
            'max_select' => $group->max_select,
            'options' => $group->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'price_delta' => $option->price_delta,
            ])->all(),
        ])->all();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function addGroup(): void
    {
        $this->groups[] = [
            'id' => null,
            'name' => '',
            'is_required' => false,
            'max_select' => 1,
            'options' => [['id' => null, 'name' => '', 'price_delta' => 0]],
        ];
    }

    public function removeGroup(int $index): void
    {
        unset($this->groups[$index]);
        $this->groups = array_values($this->groups);
    }

    public function addOption(int $groupIndex): void
    {
        $this->groups[$groupIndex]['options'][] = ['id' => null, 'name' => '', 'price_delta' => 0];
    }

    public function removeOption(int $groupIndex, int $optionIndex): void
    {
        unset($this->groups[$groupIndex]['options'][$optionIndex]);
        $this->groups[$groupIndex]['options'] = array_values($this->groups[$groupIndex]['options']);
    }

    public function save(): void
    {
        Gate::authorize('menu.manage');

        $this->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'sku' => ['required', 'string', 'max:20', Rule::unique('products', 'sku')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'cost_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'stock_qty' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'groups.*.name' => ['required', 'string', 'max:50'],
            'groups.*.max_select' => ['required', 'integer', 'min:1', 'max:20'],
            'groups.*.options' => ['required', 'array', 'min:1'],
            'groups.*.options.*.name' => ['required', 'string', 'max:50'],
            'groups.*.options.*.price_delta' => ['required', 'integer', 'min:0', 'max:100000000'],
        ], [], [
            'groups.*.name' => 'nama grup varian',
            'groups.*.max_select' => 'maksimal pilihan',
            'groups.*.options' => 'opsi',
            'groups.*.options.*.name' => 'nama opsi',
            'groups.*.options.*.price_delta' => 'tambahan harga',
        ]);

        if ($this->photo) {
            $this->image_path = $this->photo->store('products', 'public');
        }

        app(SaveProduct::class)->handle(
            $this->editingId ? Product::findOrFail($this->editingId) : null,
            [
                'category_id' => (int) $this->category_id,
                'sku' => $this->sku,
                'name' => $this->name,
                'price' => (int) $this->price,
                'cost_price' => (int) $this->cost_price,
                'is_active' => $this->is_active,
                'track_stock' => $this->track_stock,
                'stock_qty' => (int) $this->stock_qty,
                'image_path' => $this->image_path,
                'variant_groups' => collect($this->groups)->map(fn ($group) => [
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'is_required' => (bool) $group['is_required'],
                    'max_select' => (int) $group['max_select'],
                    'options' => collect($group['options'])->map(fn ($option) => [
                        'id' => $option['id'],
                        'name' => $option['name'],
                        'price_delta' => (int) $option['price_delta'],
                    ])->all(),
                ])->all(),
            ],
            Auth::user(),
        );

        $this->dispatch('toast', type: 'success', message: 'Produk disimpan.');
        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleActive(int $productId): void
    {
        Gate::authorize('menu.manage');
        $product = Product::findOrFail($productId);
        $product->update(['is_active' => ! $product->is_active]);

        $this->dispatch('toast', type: 'success', message: 'Status produk diperbarui.');
    }

    public function delete(int $productId): void
    {
        Gate::authorize('menu.manage');
        app(DeleteProduct::class)->handle(Product::findOrFail($productId));

        $this->dispatch('toast', type: 'success', message: 'Produk dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->category_id = '';
        $this->sku = '';
        $this->name = '';
        $this->price = 0;
        $this->cost_price = 0;
        $this->is_active = true;
        $this->track_stock = false;
        $this->stock_qty = 0;
        $this->photo = null;
        $this->image_path = null;
        $this->groups = [];
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

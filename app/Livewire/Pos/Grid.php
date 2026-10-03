<?php

namespace App\Livewire\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Grid extends Component
{
    public string $categoryFilter = '';

    public ?int $selectingProductId = null;

    /** @var array<int, int|string|bool|array<int, int|string>> groupId => optionId (max 1) atau daftar optionId */
    public array $selected = [];

    public function mount(): void
    {
        Gate::authorize('pos.transact');
    }

    public function render()
    {
        $products = Product::query()
            ->active()
            ->with('category')
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->orderBy('name')
            ->get();

        $selecting = $this->selectingProductId
            ? Product::active()->with('variantGroups.options')->find($this->selectingProductId)
            : null;

        return view('livewire.pos.grid', [
            'products' => $products,
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
            'selecting' => $selecting,
            'stock' => app(StockService::class),
        ]);
    }

    public function selectProduct(int $productId): void
    {
        Gate::authorize('pos.transact');
        $product = Product::active()->with('variantGroups.options')->findOrFail($productId);

        if (! app(StockService::class)->isSellable($product)) {
            $this->dispatch('toast', type: 'warning', message: $product->name.' habis.');

            return;
        }

        if ($product->variantGroups->isEmpty()) {
            $this->addToCart($product, []);

            return;
        }

        $this->selected = [];
        $this->selectingProductId = $product->id;
    }

    public function closeModal(): void
    {
        $this->selectingProductId = null;
        $this->selected = [];
        $this->resetErrorBag();
    }

    public function confirmOptions(): void
    {
        Gate::authorize('pos.transact');
        $product = Product::active()->with('variantGroups.options')->findOrFail($this->selectingProductId);

        $chosen = [];
        foreach ($product->variantGroups as $group) {
            $raw = $this->selected[$group->id] ?? [];
            $ids = collect(is_array($raw) ? $raw : [$raw])
                ->filter(fn ($id) => $id !== '' && $id !== false && $id !== null)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($ids->diff($group->options->pluck('id'))->isNotEmpty()) {
                $this->addError('selected.'.$group->id, 'Pilihan tidak valid.');

                return;
            }

            if ($group->is_required && $ids->isEmpty()) {
                $this->addError('selected.'.$group->id, 'Pilih salah satu '.mb_strtolower($group->name).'.');

                return;
            }

            if ($ids->count() > $group->max_select) {
                $this->addError('selected.'.$group->id, 'Maksimal '.$group->max_select.' pilihan.');

                return;
            }

            $chosen = array_merge($chosen, $ids->all());
        }

        $this->addToCart($product, $chosen);
        $this->closeModal();
    }

    /**
     * Keranjang dibangun di F3; di sini hanya mengumumkan pilihan (id saja — harga dihitung ulang server).
     *
     * @param  list<int>  $optionIds
     */
    private function addToCart(Product $product, array $optionIds): void
    {
        $this->dispatch('cart-add', productId: $product->id, optionIds: $optionIds);
        $this->dispatch('toast', type: 'success', message: $product->name.' dipilih.');
    }
}

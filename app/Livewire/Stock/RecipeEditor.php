<?php

namespace App\Livewire\Stock;

use App\Actions\Stock\SaveRecipe;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class RecipeEditor extends Component
{
    public string $productId = '';

    /** @var list<array{ingredient_id: int|string, qty: int|string}> */
    public array $baseLines = [];

    /** @var array<int, list<array{ingredient_id: int|string, qty: int|string}>> variant_option_id => baris */
    public array $optionLines = [];

    public function mount(): void
    {
        Gate::authorize('stock.manage');
    }

    public function render()
    {
        return view('livewire.stock.recipe-editor', [
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'ingredients' => Ingredient::query()->orderBy('name')->get(),
            'product' => $this->loadProduct(),
        ]);
    }

    public function selectProduct(): void
    {
        Gate::authorize('stock.manage');
        $product = $this->loadProduct();

        $this->baseLines = $product
            ? $product->recipes->map(fn ($r) => ['ingredient_id' => $r->ingredient_id, 'qty' => (string) $r->qty])->all()
            : [];

        $this->optionLines = [];
        foreach ($product?->variantGroups ?? [] as $group) {
            foreach ($group->options as $option) {
                $this->optionLines[$option->id] = $option->recipes
                    ->map(fn ($r) => ['ingredient_id' => $r->ingredient_id, 'qty' => (string) $r->qty])
                    ->all();
            }
        }
    }

    public function addBaseLine(): void
    {
        $this->baseLines[] = ['ingredient_id' => '', 'qty' => 0];
    }

    public function removeBaseLine(int $index): void
    {
        unset($this->baseLines[$index]);
        $this->baseLines = array_values($this->baseLines);
    }

    public function addOptionLine(int $optionId): void
    {
        $this->optionLines[$optionId][] = ['ingredient_id' => '', 'qty' => 0];
    }

    public function removeOptionLine(int $optionId, int $index): void
    {
        unset($this->optionLines[$optionId][$index]);
        $this->optionLines[$optionId] = array_values($this->optionLines[$optionId]);
    }

    public function save(): void
    {
        Gate::authorize('stock.manage');

        $this->validate([
            'productId' => ['required', 'exists:products,id'],
            'baseLines.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'baseLines.*.qty' => ['required', 'numeric', 'min:0.001'],
            'optionLines.*.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'optionLines.*.*.qty' => ['required', 'numeric', 'min:0.001'],
        ]);

        $product = Product::with('variantGroups.options')->findOrFail($this->productId);

        app(SaveRecipe::class)->handle(
            $product,
            collect($this->baseLines)->map(fn ($l) => ['ingredient_id' => (int) $l['ingredient_id'], 'qty' => $l['qty']])->all(),
            collect($this->optionLines)
                ->map(fn ($lines) => collect($lines)->map(fn ($l) => ['ingredient_id' => (int) $l['ingredient_id'], 'qty' => $l['qty']])->all())
                ->all(),
        );

        $this->dispatch('toast', type: 'success', message: 'Resep disimpan.');
        $this->selectProduct();
    }

    private function loadProduct(): ?Product
    {
        return $this->productId !== ''
            ? Product::with('variantGroups.options.recipes', 'recipes')->find($this->productId)
            : null;
    }
}

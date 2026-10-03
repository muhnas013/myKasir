<?php

namespace App\Livewire\Stock;

use App\Actions\Stock\DeleteIngredient;
use App\Actions\Stock\SaveIngredient;
use App\Models\Ingredient;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class IngredientTable extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $unit = 'g';

    public int|string $min_qty = 0;

    public ?int $purchaseId = null;

    public int|string $purchase_qty = 0;

    public int|string $purchase_total_cost = 0;

    public ?int $opnameId = null;

    public int|string $opname_qty = 0;

    public string $opname_reason = '';

    public function mount(): void
    {
        Gate::authorize('stock.manage');
    }

    public function render()
    {
        $ingredients = Ingredient::query()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();

        return view('livewire.stock.ingredient-table', [
            'ingredients' => $ingredients,
            'lowStock' => Ingredient::query()->lowStock()->orderBy('name')->get(),
        ]);
    }

    public function create(): void
    {
        Gate::authorize('stock.manage');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $ingredientId): void
    {
        Gate::authorize('stock.manage');
        $ingredient = Ingredient::findOrFail($ingredientId);

        $this->editingId = $ingredient->id;
        $this->name = $ingredient->name;
        $this->unit = $ingredient->unit;
        $this->min_qty = (string) $ingredient->min_qty;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        Gate::authorize('stock.manage');

        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('ingredients', 'name')->ignore($this->editingId)],
            'unit' => ['required', Rule::in(['g', 'ml', 'pcs'])],
            'min_qty' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);

        app(SaveIngredient::class)->handle(
            $this->editingId ? Ingredient::findOrFail($this->editingId) : null,
            ['name' => $this->name, 'unit' => $this->unit, 'min_qty' => $this->min_qty],
        );

        $this->dispatch('toast', type: 'success', message: 'Bahan disimpan.');
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $ingredientId): void
    {
        Gate::authorize('stock.manage');

        try {
            app(DeleteIngredient::class)->handle(Ingredient::findOrFail($ingredientId));
            $this->dispatch('toast', type: 'success', message: 'Bahan dihapus.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());
        }
    }

    public function openPurchase(int $ingredientId): void
    {
        Gate::authorize('stock.manage');
        $this->purchaseId = $ingredientId;
        $this->purchase_qty = 0;
        $this->purchase_total_cost = 0;
        $this->resetErrorBag();
    }

    public function closePurchase(): void
    {
        $this->purchaseId = null;
    }

    public function receivePurchase(): void
    {
        Gate::authorize('stock.manage');

        $this->validate([
            'purchase_qty' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'purchase_total_cost' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ]);

        try {
            app(StockService::class)->receivePurchase(
                Ingredient::findOrFail($this->purchaseId),
                (string) $this->purchase_qty,
                (int) $this->purchase_total_cost,
                Auth::user(),
            );
            $this->dispatch('toast', type: 'success', message: 'Stok masuk dicatat.');
            $this->closePurchase();
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());
        }
    }

    public function openOpname(int $ingredientId): void
    {
        Gate::authorize('stock.manage');
        $ingredient = Ingredient::findOrFail($ingredientId);

        $this->opnameId = $ingredientId;
        $this->opname_qty = (string) $ingredient->stock_qty;
        $this->opname_reason = '';
        $this->resetErrorBag();
    }

    public function closeOpname(): void
    {
        $this->opnameId = null;
    }

    public function saveOpname(): void
    {
        Gate::authorize('stock.manage');

        $this->validate([
            'opname_qty' => ['required', 'numeric', 'min:0', 'max:999999'],
            'opname_reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            app(StockService::class)->adjustStock(
                Ingredient::findOrFail($this->opnameId),
                (string) $this->opname_qty,
                $this->opname_reason,
                Auth::user(),
            );
            $this->dispatch('toast', type: 'success', message: 'Opname dicatat.');
            $this->closeOpname();
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->unit = 'g';
        $this->min_qty = 0;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

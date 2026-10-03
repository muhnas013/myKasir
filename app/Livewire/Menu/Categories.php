<?php

namespace App\Livewire\Menu;

use App\Actions\Menu\DeleteCategory;
use App\Actions\Menu\SaveCategory;
use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Categories extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public int|string $sort_order = 0;

    public function mount(): void
    {
        Gate::authorize('menu.manage');
    }

    public function render()
    {
        return view('livewire.menu.categories', [
            'categories' => Category::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): void
    {
        Gate::authorize('menu.manage');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $categoryId): void
    {
        Gate::authorize('menu.manage');
        $category = Category::findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->sort_order = $category->sort_order;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        Gate::authorize('menu.manage');

        $this->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($this->editingId)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        app(SaveCategory::class)->handle(
            $this->editingId ? Category::findOrFail($this->editingId) : null,
            ['name' => $this->name, 'sort_order' => (int) $this->sort_order],
        );

        $this->dispatch('toast', type: 'success', message: 'Kategori disimpan.');
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $categoryId): void
    {
        Gate::authorize('menu.manage');

        try {
            app(DeleteCategory::class)->handle(Category::findOrFail($categoryId));
            $this->dispatch('toast', type: 'success', message: 'Kategori dihapus.');
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->sort_order = 0;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

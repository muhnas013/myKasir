<?php

namespace App\Livewire\Stock;

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Stok')]
class Index extends Component
{
    public string $tab = 'bahan';

    public function mount(): void
    {
        Gate::authorize('stock.manage');
    }

    public function render()
    {
        return view('livewire.stock.index');
    }
}

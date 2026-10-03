<?php

namespace App\Livewire\Menu;

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Menu')]
class Index extends Component
{
    public string $tab = 'products';

    public function mount(): void
    {
        Gate::authorize('menu.manage');
    }

    public function render()
    {
        return view('livewire.menu.index');
    }
}

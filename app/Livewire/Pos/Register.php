<?php

namespace App\Livewire\Pos;

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kasir')]
class Register extends Component
{
    public function mount(): void
    {
        Gate::authorize('pos.transact');
    }

    public function render()
    {
        return view('livewire.pos.register');
    }
}

<?php

namespace App\Livewire\Payroll;

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Penggajian')]
class Index extends Component
{
    public string $tab = 'summary';

    public function mount(): void
    {
        Gate::authorize('settings.manage');
    }

    public function render()
    {
        return view('livewire.payroll.index');
    }
}

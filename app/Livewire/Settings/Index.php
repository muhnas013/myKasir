<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pengaturan')]
class Index extends Component
{
    public string $tab = 'general';

    public function render()
    {
        return view('livewire.settings.index');
    }
}

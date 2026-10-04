<?php

namespace App\Livewire\Shift;

use App\Actions\Auth\VerifyOwnPin;
use App\Actions\Shifts\OpenShift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buka Shift')]
class Open extends Component
{
    public int|string $opening_cash = 0;

    public string $pin = '';

    public function mount()
    {
        Gate::authorize('pos.transact');

        if (Auth::user()->activeShift()) {
            return $this->redirectRoute('pos.index');
        }
    }

    public function save()
    {
        Gate::authorize('pos.transact');

        $this->validate([
            'opening_cash' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'pin' => ['required', 'digits:6'],
        ]);

        try {
            app(VerifyOwnPin::class)->handle(Auth::user(), $this->pin);
        } catch (ValidationException $e) {
            $this->addError('pin', $e->validator->errors()->first());
            $this->pin = '';

            return;
        }

        try {
            app(OpenShift::class)->handle(Auth::user(), (int) $this->opening_cash);
        } catch (ValidationException $e) {
            $this->addError('opening_cash', $e->validator->errors()->first());

            return;
        }

        return $this->redirectRoute('pos.index');
    }

    public function render()
    {
        return view('livewire.shift.open');
    }
}

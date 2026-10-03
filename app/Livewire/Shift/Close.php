<?php

namespace App\Livewire\Shift;

use App\Actions\Shifts\CloseShift;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tutup Shift')]
class Close extends Component
{
    public int|string $counted_cash = 0;

    public string $closing_note = '';

    #[Locked]
    public ?int $closedShiftId = null;

    public function mount(): void
    {
        Gate::authorize('pos.transact');
    }

    public function save(): void
    {
        Gate::authorize('pos.transact');

        $this->validate([
            'counted_cash' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'closing_note' => ['nullable', 'string', 'max:500'],
        ]);

        $shift = Auth::user()->activeShift();
        if (! $shift) {
            $this->addError('counted_cash', 'Tidak ada shift yang terbuka.');

            return;
        }

        try {
            $closed = app(CloseShift::class)->handle($shift, Auth::user(), (int) $this->counted_cash, $this->closing_note);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError(in_array($field, ['counted_cash', 'closing_note'], true) ? $field : 'shift', $messages[0]);
            }

            return;
        }

        $this->closedShiftId = $closed->id;
        $this->dispatch('toast', type: 'success', message: 'Shift ditutup.');
    }

    public function render()
    {
        return view('livewire.shift.close', [
            'closed' => $this->closedShiftId ? Shift::find($this->closedShiftId) : null,
        ]);
    }
}

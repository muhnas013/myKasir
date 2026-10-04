<?php

namespace App\Livewire\Shift;

use App\Actions\Shifts\RecordShiftExpense;
use App\Models\ShiftExpense;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pengeluaran Kas')]
class Expenses extends Component
{
    public string $description = '';

    public int|string $amount = 0;

    public function mount(): void
    {
        Gate::authorize('pos.transact');
    }

    public function render()
    {
        $shift = Auth::user()->activeShift();

        return view('livewire.shift.expenses', [
            'shift' => $shift,
            'expenses' => $shift ? $shift->expenses()->orderByDesc('created_at')->get() : collect(),
        ]);
    }

    public function save(): void
    {
        Gate::authorize('pos.transact');

        $this->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ]);

        $shift = Auth::user()->activeShift();
        if (! $shift) {
            $this->addError('amount', 'Tidak ada shift yang terbuka.');

            return;
        }

        try {
            app(RecordShiftExpense::class)->handle($shift, Auth::user(), $this->description, (int) $this->amount);
        } catch (ValidationException $e) {
            $this->dispatch('toast', type: 'danger', message: $e->validator->errors()->first());

            throw $e;
        }

        $this->reset(['description', 'amount']);
        $this->dispatch('toast', type: 'success', message: 'Pengeluaran dicatat.');
    }

    public function delete(int $expenseId): void
    {
        Gate::authorize('pos.transact');

        $shift = Auth::user()->activeShift();
        $expense = ShiftExpense::findOrFail($expenseId);

        if (! $shift || $expense->shift_id !== $shift->id) {
            $this->dispatch('toast', type: 'danger', message: 'Pengeluaran bukan milik shift Anda yang sedang berjalan.');

            return;
        }

        $expense->delete();
        $this->dispatch('toast', type: 'success', message: 'Pengeluaran dihapus.');
    }
}

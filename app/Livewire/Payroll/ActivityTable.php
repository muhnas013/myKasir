<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\SaveWageActivity;
use App\Models\WageActivity;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ActivityTable extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public int|string $bonus_amount = 0;

    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('settings.manage');
    }

    public function render()
    {
        return view('livewire.payroll.activity-table', [
            'activities' => WageActivity::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): void
    {
        Gate::authorize('settings.manage');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $activityId): void
    {
        Gate::authorize('settings.manage');
        $activity = WageActivity::findOrFail($activityId);

        $this->editingId = $activity->id;
        $this->name = $activity->name;
        $this->bonus_amount = $activity->bonus_amount;
        $this->is_active = $activity->is_active;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        Gate::authorize('settings.manage');

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'bonus_amount' => ['required', 'integer', 'min:0', 'max:100000000'],
        ]);

        app(SaveWageActivity::class)->handle(
            $this->editingId ? WageActivity::findOrFail($this->editingId) : null,
            [
                'name' => $this->name,
                'bonus_amount' => (int) $this->bonus_amount,
                'is_active' => $this->is_active,
            ],
        );

        $this->dispatch('toast', type: 'success', message: 'Aktivitas bonus disimpan.');
        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleActive(int $activityId): void
    {
        Gate::authorize('settings.manage');
        $activity = WageActivity::findOrFail($activityId);
        $activity->update(['is_active' => ! $activity->is_active]);

        $this->dispatch('toast', type: 'success', message: 'Status aktivitas diperbarui.');
    }

    public function delete(int $activityId): void
    {
        Gate::authorize('settings.manage');
        WageActivity::findOrFail($activityId)->delete();

        $this->dispatch('toast', type: 'success', message: 'Aktivitas dihapus.');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->bonus_amount = 0;
        $this->is_active = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

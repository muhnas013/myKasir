<?php

namespace App\Livewire\Payroll;

use App\Services\WageCalculator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class WageSummary extends Component
{
    public string $period = 'today';

    public string $start = '';

    public string $end = '';

    public function mount(): void
    {
        Gate::authorize('settings.manage');

        $this->start = now()->toDateString();
        $this->end = now()->toDateString();
    }

    public function updatedPeriod(): void
    {
        [$this->start, $this->end] = $this->resolveRange();
    }

    public function render(WageCalculator $calculator)
    {
        [$start, $end] = $this->resolveRange();
        $rows = $calculator->forRange($start, $end);

        return view('livewire.payroll.wage-summary', [
            'rows' => $rows,
            'summary' => $calculator->summaryByUser($rows),
            'rangeStart' => $start,
            'rangeEnd' => $end,
        ]);
    }

    /** @return array{0: string, 1: string} */
    private function resolveRange(): array
    {
        $today = now()->toDateString();

        return match ($this->period) {
            '7' => [now()->subDays(6)->toDateString(), $today],
            '30' => [now()->subDays(29)->toDateString(), $today],
            'custom' => [
                $this->start !== '' ? $this->start : $today,
                $this->end !== '' ? $this->end : $today,
            ],
            default => [$today, $today],
        };
    }
}

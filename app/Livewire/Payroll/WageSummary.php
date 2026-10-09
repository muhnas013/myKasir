<?php

namespace App\Livewire\Payroll;

use App\Services\WageCalculator;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class WageSummary extends Component
{
    public string $period = '';

    public string $start = '';

    public string $end = '';

    public function mount(): void
    {
        Gate::authorize('settings.manage');
    }

    public function updatedPeriod(): void
    {
        if ($this->period === 'custom') {
            $this->start = $this->start !== '' ? $this->start : now()->toDateString();
            $this->end = $this->end !== '' ? $this->end : now()->toDateString();
        }
    }

    public function render(WageCalculator $calculator)
    {
        $range = $this->resolveRange();
        $rows = $range !== null ? $calculator->forRange($range[0], $range[1]) : collect();

        return view('livewire.payroll.wage-summary', [
            'periodChosen' => $range !== null,
            'rows' => $rows,
            'summary' => $calculator->summaryByUser($rows),
            'rangeStart' => $range[0] ?? null,
            'rangeEnd' => $range[1] ?? null,
        ]);
    }

    /** @return array{0: string, 1: string}|null null bila periode belum dipilih pengguna */
    private function resolveRange(): ?array
    {
        $today = now()->toDateString();

        return match ($this->period) {
            'today' => [$today, $today],
            '7' => [now()->subDays(6)->toDateString(), $today],
            '30' => [now()->subDays(29)->toDateString(), $today],
            'custom' => $this->start !== '' && $this->end !== '' ? [$this->start, $this->end] : null,
            default => null,
        };
    }
}

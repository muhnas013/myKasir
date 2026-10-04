<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Laporan')]
class Index extends Component
{
    public string $period = 'today';

    public string $start = '';

    public string $end = '';

    public function mount(): void
    {
        Gate::authorize('report.view-own');

        $this->start = now()->toDateString();
        $this->end = now()->toDateString();
    }

    public function updatedPeriod(): void
    {
        [$this->start, $this->end] = $this->resolveRange();
    }

    public function render(ReportService $reports)
    {
        $user = Auth::user();
        $canViewAll = Gate::allows('report.view-all');
        $userId = $canViewAll ? null : $user->id;

        [$start, $end] = $this->resolveRange();

        return view('livewire.reports.index', [
            'canViewAll' => $canViewAll,
            'summary' => $reports->summary($start, $end, $userId),
            'hourly' => $reports->hourly($start, $end, $userId),
            'topProducts' => $reports->topProducts($start, $end, $userId),
            'paymentMethods' => $reports->paymentMethods($start, $end, $userId),
            'history' => $reports->history($start, $end, $userId),
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

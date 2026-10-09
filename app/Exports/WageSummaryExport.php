<?php

namespace App\Exports;

use App\Services\WageCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WageSummaryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function collection(): Collection
    {
        $calculator = app(WageCalculator::class);
        $rows = $calculator->forRange($this->startDate, $this->endDate);

        return collect($calculator->summaryByUser($rows))->values();
    }

    public function headings(): array
    {
        return ['No', 'Pegawai', 'Omzet', 'Upah Dasar', 'Bonus Aktivitas', 'Bonus Penjualan', 'Total Upah'];
    }

    /** @param  array<string, mixed>  $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row['name'],
            $row['omzet'],
            $row['base'],
            $row['activity_total'],
            $row['sales_bonus'],
            $row['total'],
        ];
    }
}

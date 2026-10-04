<?php

namespace App\Exports;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\ReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly ?int $userId = null,
    ) {}

    public function collection(): Collection
    {
        return app(ReportService::class)->history($this->startDate, $this->endDate, $this->userId);
    }

    public function headings(): array
    {
        return ['No', 'Waktu', 'Kasir', 'Item', 'Metode', 'Subtotal', 'Diskon', 'Layanan', 'PB1', 'Total', 'Status'];
    }

    /** @param  Order  $order */
    public function map($order): array
    {
        static $no = 0;
        $no++;

        $items = $order->items->map(fn ($item) => $item->qty.'x '.$item->product_name)->implode(', ');

        return [
            $no,
            optional($order->paid_at ?? $order->created_at)->format('d/m/Y H.i'),
            $order->user->name,
            $items,
            $order->payment?->method?->label() ?? '-',
            $order->subtotal,
            $order->discount,
            $order->service,
            $order->tax,
            $order->total,
            $order->status === OrderStatus::Void ? 'Void' : 'Lunas',
        ];
    }
}

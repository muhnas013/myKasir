<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Agregasi laporan read-only di atas orders/order_items/payments yang sudah dibayar.
 * Pesanan void tidak pernah dihitung.
 */
class ReportService
{
    public function summary(string $startDate, string $endDate, ?int $userId = null): array
    {
        $omzet = (int) $this->paidOrdersQuery($startDate, $endDate, $userId)->sum('total');
        $transaksi = (clone $this->paidOrdersQuery($startDate, $endDate, $userId))->count();

        return [
            'omzet' => $omzet,
            'transaksi' => $transaksi,
            'rata_rata' => $transaksi > 0 ? (int) round($omzet / $transaksi) : 0,
        ];
    }

    /** Total omzet per jam (0-23), jam mengikuti timezone aplikasi (Asia/Makassar). */
    public function hourly(string $startDate, string $endDate, ?int $userId = null): array
    {
        $rows = $this->paidOrdersQuery($startDate, $endDate, $userId)
            ->selectRaw('HOUR(paid_at) as jam, SUM(total) as total')
            ->groupBy('jam')
            ->pluck('total', 'jam');

        return collect(range(0, 23))
            ->mapWithKeys(fn (int $jam) => [$jam => (int) ($rows[$jam] ?? 0)])
            ->all();
    }

    public function topProducts(string $startDate, string $endDate, ?int $userId = null, int $limit = 10): Collection
    {
        return OrderItem::query()
            ->whereHas('order', fn (Builder $q) => $this->scopePaidOrders($q, $startDate, $endDate, $userId))
            ->selectRaw('product_name, SUM(qty) as qty, SUM(line_total) as total')
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();
    }

    public function paymentMethods(string $startDate, string $endDate, ?int $userId = null): Collection
    {
        return Payment::query()
            ->whereHas('order', fn (Builder $q) => $this->scopePaidOrders($q, $startDate, $endDate, $userId))
            ->selectRaw('method, COUNT(*) as transaksi, SUM(amount) as total')
            ->groupBy('method')
            ->get();
    }

    /** Riwayat mencakup transaksi `paid` dan `void` (pesanan `open` belum final, tak masuk riwayat). */
    public function history(string $startDate, string $endDate, ?int $userId = null): Collection
    {
        return Order::query()
            ->whereBetween('business_date', [$startDate, $endDate])
            ->where('status', '!=', OrderStatus::Open)
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId))
            ->with(['items', 'payment', 'user'])
            ->orderBy('business_date')
            ->orderBy('id')
            ->get();
    }

    private function paidOrdersQuery(string $startDate, string $endDate, ?int $userId): Builder
    {
        return $this->scopePaidOrders(Order::query(), $startDate, $endDate, $userId);
    }

    private function scopePaidOrders(Builder $query, string $startDate, string $endDate, ?int $userId): Builder
    {
        return $query
            ->where('status', OrderStatus::Paid)
            ->whereBetween('business_date', [$startDate, $endDate])
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId));
    }
}

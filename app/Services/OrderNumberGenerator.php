<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonInterface;

class OrderNumberGenerator
{
    /** Panggil di dalam DB::transaction; indeks unik (business_date, number) jadi pengaman terakhir. */
    public function next(CarbonInterface $businessDate): string
    {
        $last = Order::query()
            ->whereDate('business_date', $businessDate)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('number');

        $sequence = $last ? ((int) substr($last, 2)) + 1 : 1;

        return sprintf('A-%04d', $sequence);
    }
}

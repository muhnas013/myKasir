<?php

namespace App\Services;

/**
 * Satu-satunya tempat perhitungan uang (docs/06 "Aturan perhitungan").
 * Semua integer Rupiah; persen dalam basis poin (1000 = 10%); pembulatan half-up.
 */
class PriceCalculator
{
    /** amount × bp / 10000, dibulatkan half-up ke Rupiah. */
    public function applyBp(int $amount, int $bp): int
    {
        return intdiv($amount * $bp + 5000, 10000);
    }

    /**
     * @param  'amount'|'percent'  $type  percent: 0–100
     */
    public function discount(int $subtotal, string $type, int $value): int
    {
        $discount = $type === 'percent'
            ? $this->applyBp($subtotal, max(0, min(100, $value)) * 100)
            : max(0, $value);

        return min($discount, $subtotal);
    }

    /**
     * @return array{subtotal: int, discount: int, service: int, tax: int, rounding: int, total: int}
     */
    public function totals(
        int $subtotal,
        int $discount,
        bool $dineIn,
        bool $serviceEnabled,
        int $serviceRateBp,
        bool $taxEnabled,
        int $taxRateBp,
        int $roundingUnit = 100,
    ): array {
        $net = $subtotal - $discount;
        $service = ($dineIn && $serviceEnabled) ? $this->applyBp($net, $serviceRateBp) : 0;
        $tax = $taxEnabled ? $this->applyBp($net + $service, $taxRateBp) : 0;

        $raw = $net + $service + $tax;
        $unit = max(1, $roundingUnit);
        $total = intdiv($raw + intdiv($unit, 2), $unit) * $unit;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'service' => $service,
            'tax' => $tax,
            'rounding' => $total - $raw,
            'total' => $total,
        ];
    }
}

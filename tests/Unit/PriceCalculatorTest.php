<?php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private function calc(): PriceCalculator
    {
        return new PriceCalculator;
    }

    public function test_take_away_with_tax_only(): void
    {
        $t = $this->calc()->totals(73000, 0, false, false, 500, true, 1000);

        $this->assertSame(0, $t['service']);
        $this->assertSame(7300, $t['tax']);
        $this->assertSame(0, $t['rounding']);
        $this->assertSame(80300, $t['total']);
    }

    public function test_dine_in_with_service_and_tax_rounds_to_hundred(): void
    {
        $t = $this->calc()->totals(73000, 0, true, true, 500, true, 1000);

        $this->assertSame(3650, $t['service']);
        $this->assertSame(7665, $t['tax']);
        $this->assertSame(-15, $t['rounding']);
        $this->assertSame(84300, $t['total']);
    }

    public function test_service_is_ignored_for_take_away(): void
    {
        $t = $this->calc()->totals(73000, 0, false, true, 500, false, 0);

        $this->assertSame(0, $t['service']);
        $this->assertSame(73000, $t['total']);
    }

    public function test_discount_reduces_base_for_service_and_tax(): void
    {
        $t = $this->calc()->totals(73000, 3000, false, false, 0, true, 1000);

        $this->assertSame(7000, $t['tax']);
        $this->assertSame(77000, $t['total']);
    }

    public function test_rounding_is_half_up_to_unit(): void
    {
        // 150 → naik ke 200 (half-up), 149 → turun ke 100
        $this->assertSame(200, $this->calc()->totals(150, 0, false, false, 0, false, 0)['total']);
        $this->assertSame(100, $this->calc()->totals(149, 0, false, false, 0, false, 0)['total']);
        $this->assertSame(1, $this->calc()->totals(149, 0, false, false, 0, false, 0, 1)['total'] - 148);
    }

    public function test_bp_application_rounds_half_up(): void
    {
        $this->assertSame(1, $this->calc()->applyBp(5, 1000)); // 0,5 → 1
        $this->assertSame(0, $this->calc()->applyBp(4, 1000)); // 0,4 → 0
    }

    public function test_percent_discount_and_cap(): void
    {
        $c = $this->calc();

        $this->assertSame(7300, $c->discount(73000, 'percent', 10));
        $this->assertSame(5000, $c->discount(73000, 'amount', 5000));
        $this->assertSame(73000, $c->discount(73000, 'amount', 999999));
        $this->assertSame(73000, $c->discount(73000, 'percent', 500));
        $this->assertSame(0, $c->discount(73000, 'amount', -10));
    }
}

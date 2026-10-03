<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_rupiah_with_thousand_dots(): void
    {
        $this->assertSame('Rp 18.000', Money::format(18000));
        $this->assertSame('Rp 0', Money::format(0));
        $this->assertSame('Rp 1.250.000', Money::format(1250000));
    }

    public function test_formats_negative_amounts(): void
    {
        $this->assertSame('-Rp 5.000', Money::format(-5000));
    }
}

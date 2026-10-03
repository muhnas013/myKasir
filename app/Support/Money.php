<?php

namespace App\Support;

class Money
{
    /** Format Rupiah utuh: 18000 → "Rp 18.000". */
    public static function format(int $amount): string
    {
        $formatted = number_format(abs($amount), 0, ',', '.');

        return ($amount < 0 ? '-' : '').'Rp '.$formatted;
    }
}

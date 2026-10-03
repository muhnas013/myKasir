<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case Paid = 'paid';
    case Void = 'void';
}

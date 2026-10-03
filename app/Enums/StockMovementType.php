<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case VoidReturn = 'void_return';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
}

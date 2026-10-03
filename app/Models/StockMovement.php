<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'ingredient_id',
        'product_id',
        'type',
        'qty',
        'total_cost',
        'order_id',
        'user_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'qty' => 'decimal:3',
        ];
    }
}

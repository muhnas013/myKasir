<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'number',
        'business_date',
        'shift_id',
        'user_id',
        'status',
        'order_type',
        'customer_label',
        'note',
        'subtotal',
        'discount',
        'service',
        'tax',
        'rounding',
        'total',
        'discount_approved_by',
        'idempotency_key',
        'paid_at',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'status' => OrderStatus::class,
            'order_type' => OrderType::class,
            'subtotal' => 'integer',
            'discount' => 'integer',
            'service' => 'integer',
            'tax' => 'integer',
            'rounding' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}

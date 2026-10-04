<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pengeluaran kas operasional selama shift (mis. beli es batu) — mengurangi expected_cash. */
class ShiftExpense extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shift_id',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ShiftExpense $expense) {
            $expense->created_at ??= now();
        });
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}

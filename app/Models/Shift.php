<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\ShiftStatus;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'opened_at',
        'closed_at',
        'opening_cash',
        'expected_cash',
        'counted_cash',
        'cash_difference',
        'closing_note',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShiftStatus::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'integer',
            'expected_cash' => 'integer',
            'counted_cash' => 'integer',
            'cash_difference' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isOpen(): bool
    {
        return $this->status === ShiftStatus::Open;
    }

    /** Modal awal + tunai dari pesanan lunas (penerimaan − kembalian). Pesanan void tidak dihitung. */
    public function expectedCash(): int
    {
        $cash = Payment::query()
            ->where('method', PaymentMethod::Cash)
            ->whereHas('order', fn ($q) => $q->where('shift_id', $this->id)->where('status', 'paid'))
            ->selectRaw('COALESCE(SUM(paid_amount - change_amount), 0) as net')
            ->value('net');

        return $this->opening_cash + (int) $cash;
    }
}

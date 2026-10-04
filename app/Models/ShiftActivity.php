<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log aktivitas tambahan per shift (06 P8) — nama & bonus_amount adalah
 * snapshot saat dicatat, bukan rujukan langsung ke WageActivity, supaya
 * riwayat upah tak berubah bila katalog diedit/dihapus belakangan.
 */
class ShiftActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shift_id',
        'wage_activity_id',
        'name',
        'bonus_amount',
    ];

    protected function casts(): array
    {
        return [
            'bonus_amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ShiftActivity $activity) {
            $activity->created_at ??= now();
        });
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function wageActivity(): BelongsTo
    {
        return $this->belongsTo(WageActivity::class);
    }
}

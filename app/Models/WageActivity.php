<?php

namespace App\Models;

use Database\Factories\WageActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WageActivity extends Model
{
    /** @use HasFactory<WageActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'bonus_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bonus_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function shiftActivities(): HasMany
    {
        return $this->hasMany(ShiftActivity::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

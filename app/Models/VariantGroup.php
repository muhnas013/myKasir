<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantGroup extends Model
{
    protected $fillable = ['product_id', 'name', 'is_required', 'max_select'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'max_select' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class);
    }
}

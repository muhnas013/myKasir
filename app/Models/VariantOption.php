<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantOption extends Model
{
    protected $fillable = ['variant_group_id', 'name', 'price_delta'];

    protected function casts(): array
    {
        return [
            'price_delta' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(VariantGroup::class, 'variant_group_id');
    }
}

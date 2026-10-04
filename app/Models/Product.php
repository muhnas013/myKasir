<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'price',
        'cost_price',
        'image_path',
        'is_active',
        'track_stock',
        'stock_qty',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cost_price' => 'integer',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'stock_qty' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variantGroups(): HasMany
    {
        return $this->hasMany(VariantGroup::class);
    }

    /** Resep dasar (per 1 porsi) — bukan resep opsi varian, lihat VariantOption::recipes(). */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
        );
    }
}

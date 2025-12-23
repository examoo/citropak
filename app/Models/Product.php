<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PRODUCT MODEL (GLOBAL)
 * 
 * Shared across all distributions.
 * NO distribution_id - managed only by Super Admin.
 * 
 * NOTE: Distribution-specific stock is managed in stock_ledgers table.
 */
class Product extends Model
{
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'sku',
        'unit',
        'status',
    ];

    /**
     * Get the brand for this product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the category for this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all variants for this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Scope to active products only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

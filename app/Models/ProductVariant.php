<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PRODUCT VARIANT MODEL (GLOBAL)
 * 
 * Shared across all distributions.
 * NO distribution_id - managed only by Super Admin.
 */
class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'variant_name',
        'status',
    ];

    /**
     * Get the product for this variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to active variants only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

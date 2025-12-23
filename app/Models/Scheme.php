<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SCHEME MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 */
class Scheme extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'scheme_type',
        'brand_id',
        'product_id',
        'discount_type',
        'discount_value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
    ];

    /**
     * Get the brand (if scheme_type = brand).
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the product (if scheme_type = product).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to active schemes only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

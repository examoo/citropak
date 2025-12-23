<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BRAND MODEL (GLOBAL)
 * 
 * Shared across all distributions.
 * NO distribution_id - managed only by Super Admin.
 */
class Brand extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * Get all products for this brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope to active brands only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

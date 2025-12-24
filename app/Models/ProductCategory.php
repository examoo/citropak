<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PRODUCT CATEGORY MODEL (GLOBAL)
 * 
 * Shared across all distributions.
 * NO distribution_id - managed only by Super Admin.
 */
class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    /**
     * Get all products for this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Scope to active categories only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

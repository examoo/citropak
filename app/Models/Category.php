<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CATEGORY MODEL (GLOBAL)
 * 
 * Shared across all distributions.
 * Self-referencing for hierarchical categories.
 * NO distribution_id - managed only by Super Admin.
 */
class Category extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
    ];

    /**
     * Get parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all products in this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope to root categories only.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}

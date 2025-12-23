<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SUB DISTRIBUTION MODEL (DISTRIBUTION-SCOPED)
 * 
 * Sub distributions can be:
 * - Global (distribution_id = NULL): Available to ALL distributions
 * - Distribution-specific: Available only to that distribution
 */
class SubDistribution extends Model
{
    protected $fillable = [
        'distribution_id',
        'name',
        'is_fbr',
        'status',
    ];

    protected $casts = [
        'is_fbr' => 'boolean',
    ];

    /**
     * Get the distribution this sub distribution belongs to (if any).
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Scope to get sub distributions for a specific distribution (includes global).
     */
    public function scopeForDistribution($query, $distributionId)
    {
        return $query->where(function ($q) use ($distributionId) {
            $q->whereNull('distribution_id')
              ->orWhere('distribution_id', $distributionId);
        });
    }

    /**
     * Scope to get only active sub distributions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SUB ADDRESS MODEL (DISTRIBUTION-SCOPED)
 * 
 * Sub addresses can be:
 * - Global (distribution_id = NULL): Available to ALL distributions
 * - Distribution-specific: Available only to that distribution
 */
class SubAddress extends Model
{
    protected $fillable = [
        'distribution_id',
        'name',
        'status',
    ];

    /**
     * Get the distribution this sub address belongs to (if any).
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Scope to get sub addresses for a specific distribution (includes global).
     */
    public function scopeForDistribution($query, $distributionId)
    {
        return $query->where(function ($q) use ($distributionId) {
            $q->whereNull('distribution_id')
              ->orWhere('distribution_id', $distributionId);
        });
    }

    /**
     * Scope to get only active sub addresses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

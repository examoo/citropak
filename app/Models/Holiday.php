<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HOLIDAY MODEL (OPTIONALLY DISTRIBUTION-SCOPED)
 * 
 * Holidays can be:
 * - Global (distribution_id = NULL): Applies to ALL distributions
 * - Distribution-specific: Applies only to that distribution
 */
class Holiday extends Model
{
    protected $fillable = [
        'distribution_id',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the distribution this holiday belongs to (if any).
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Scope to get holidays for a specific distribution (includes global holidays).
     */
    public function scopeForDistribution($query, $distributionId)
    {
        return $query->where(function ($q) use ($distributionId) {
            $q->whereNull('distribution_id')
              ->orWhere('distribution_id', $distributionId);
        });
    }

    /**
     * Scope to get only global holidays.
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('distribution_id');
    }

    /**
     * Check if this is a global holiday.
     */
    public function isGlobal(): bool
    {
        return is_null($this->distribution_id);
    }
}

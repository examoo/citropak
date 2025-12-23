<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CHANNEL MODEL (DISTRIBUTION-SCOPED)
 * 
 * Channels can be:
 * - Global (distribution_id = NULL): Available to ALL distributions
 * - Distribution-specific: Available only to that distribution
 * 
 * Includes ATL (Active Taxpayer List) and Advance Tax percentage.
 */
class Channel extends Model
{
    protected $fillable = [
        'distribution_id',
        'name',
        'status',
        'atl',
        'adv_tax_percent',
    ];

    protected $casts = [
        'atl' => 'boolean',
        'adv_tax_percent' => 'decimal:2',
    ];

    /**
     * Get the distribution this channel belongs to (if any).
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Scope to get channels for a specific distribution (includes global channels).
     */
    public function scopeForDistribution($query, $distributionId)
    {
        return $query->where(function ($q) use ($distributionId) {
            $q->whereNull('distribution_id')
              ->orWhere('distribution_id', $distributionId);
        });
    }

    /**
     * Scope to get only active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

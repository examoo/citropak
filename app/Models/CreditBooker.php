<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CREDIT BOOKER MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Represents staff responsible for credit collection.
 */
class CreditBooker extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'name',
        'code',
        'phone',
        'status',
    ];

    /**
     * Get the distribution this credit booker belongs to.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Scope to active credit bookers only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

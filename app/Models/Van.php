<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;

/**
 * VAN MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 */
class Van extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'code',
        'status',
    ];

    /**
     * Scope to active vans only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

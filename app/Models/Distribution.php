<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DISTRIBUTION MODEL
 * 
 * Root tenant model for multi-tenant architecture.
 * Each distribution (DMS, TMS, LMS) is an isolated business unit.
 * 
 * NOTE: This model does NOT use BaseTenantModel trait
 * as it IS the tenant itself.
 */
class Distribution extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    /**
     * Get all users belonging to this distribution.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all customers belonging to this distribution.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Scope to active distributions only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

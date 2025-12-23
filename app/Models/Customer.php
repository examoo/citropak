<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CUSTOMER MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Each distribution has its own isolated customer base.
 */
class Customer extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'name',
        'area',
        'contact',
        'status',
    ];

    /**
     * Get brand percentages for this customer.
     */
    public function brandPercentages(): HasMany
    {
        return $this->hasMany(CustomerBrandPercentage::class);
    }

    /**
     * Get invoices for this customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Scope to active customers only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

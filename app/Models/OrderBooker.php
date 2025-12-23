<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ORDER BOOKER MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 */
class OrderBooker extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'code',
        'name',
    ];

    /**
     * Get invoices for this order booker.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get targets for this order booker.
     */
    public function targets(): HasMany
    {
        return $this->hasMany(OrderBookerTarget::class);
    }
}

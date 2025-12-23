<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ROUTE MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 */
class Route extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'name',
    ];

    /**
     * Get customers on this route.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'route_customers')
                    ->withTimestamps();
    }
}

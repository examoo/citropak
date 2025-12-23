<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'status',
    ];

    /**
     * Get the distribution this route belongs to.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Get customers on this route.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'route_customers')
                    ->withTimestamps();
    }
}

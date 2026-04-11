<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'van_id',
        'code',
        'name',
        'user_id',
        'last_latitude',
        'last_longitude',
        'last_location_updated_at',
    ];

    protected $casts = [
        'last_location_updated_at' => 'datetime',
    ];

    /**
     * Update the booker's current location.
     */
    public function updateLocation($lat, $lng)
    {
        if ($lat && $lng) {
            $this->update([
                'last_latitude' => $lat,
                'last_longitude' => $lng,
                'last_location_updated_at' => now(),
            ]);
        }
    }

    /**
     * Get the van for this order booker.
     */
    public function van(): BelongsTo
    {
        return $this->belongsTo(Van::class);
    }

    /**
     * Get the user linked to this order booker.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

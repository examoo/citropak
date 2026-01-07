<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CUSTOMER MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Each distribution has its own isolated customer base.
 */
class Customer extends Model
{
    use BaseTenantModel, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'shop_name',
        'van',
        'channel',
        'channel_id',
        'category',
        'distribution_id',
        'sub_distribution',
        'day',
        'address',
        'sub_address',
        'route',
        'phone',
        'ntn_number',
        'sales_tax_number',
        'cnic',
        'percentage',
        'status',
        'atl',
        'adv_tax_percent',
        'area', // Keeping older fields just in case if they exist
        'contact', // Keeping older fields just in case if they exist
        'opening_balance',
        'sales_tax_status',
    ];

    /**
     * Get the channel this customer belongs to.
     */
    public function channelRelation(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * Get the distribution this customer belongs to.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

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

<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * CUSTOMER ATTRIBUTE MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Manages customer attributes: van, category, channel, sub_distribution, sub_address.
 * 
 * NOTE: 'sub_distribution' type is a CUSTOMER ATTRIBUTE, different from the tenant 'distribution'.
 */
class CustomerAttribute extends Model
{
    use HasFactory, BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'type',
        'value',
        'atl',
        'adv_tax_percent',
    ];

    protected $casts = [
        'atl' => 'boolean',
        'adv_tax_percent' => 'decimal:2',
    ];

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

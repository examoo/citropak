<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * STOCK LEDGER MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Tracks all inventory movements per distribution.
 */
class StockLedger extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'product_id',
        'in_qty',
        'out_qty',
        'balance',
        'type',
    ];

    /**
     * Get the product (GLOBAL).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

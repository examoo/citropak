<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MONTHLY STOCK CLOSING MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Stores monthly opening/closing stock per product.
 */
class MonthlyStockClosing extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'product_id',
        'month',
        'opening',
        'closing',
    ];

    /**
     * Get the product (GLOBAL).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

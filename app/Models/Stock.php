<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'distribution_id',
        'quantity',
        'min_quantity',
        'max_quantity',
        'unit_cost',
        'batch_number',
        'expiry_date',
        'location',
        'notes',
        // Pricing fields from product (simplified structure)
        'pieces_per_packing',
        'list_price_before_tax',
        'fed_sales_tax',
        'fed_percent',
        'retail_margin',
        'tp_rate',
        'distribution_margin',
        'invoice_price',
        'unit_price',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Get the product for this stock.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the distribution for this stock.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Scope to filter by distribution.
     */
    public function scopeForDistribution($query, $distributionId)
    {
        if ($distributionId) {
            return $query->where('distribution_id', $distributionId);
        }
        return $query;
    }

    /**
     * Check if stock is low.
     */
    public function isLow(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }
}

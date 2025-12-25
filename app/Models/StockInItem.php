<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInItem extends Model
{
    protected $fillable = [
        'stock_in_id',
        'product_id',
        'quantity',
        'cartons',
        'pieces',
        'pieces_per_carton',
        'batch_number',
        'expiry_date',
        'location',
        'unit_cost',
        // Pricing fields (simplified structure)
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
     * Get the stock in for this item.
     */
    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get total value of this item.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }
}

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
        // Pricing fields
        'list_price_before_tax',
        'fed_tax_percent',
        'fed_sales_tax',
        'net_list_price',
        'distribution_margin',
        'trade_price_before_tax',
        'fed_2',
        'sales_tax_3',
        'net_trade_price',
        'retailer_margin',
        'consumer_price_before_tax',
        'fed_5',
        'sales_tax_6',
        'net_consumer_price',
        'unit_price',
        'total_margin',
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

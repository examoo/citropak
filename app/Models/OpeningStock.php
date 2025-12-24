<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningStock extends Model
{
    protected $fillable = [
        'product_id',
        'distribution_id',
        'date',
        'cartons',
        'pieces',
        'pieces_per_carton',
        'quantity',
        'batch_number',
        'expiry_date',
        'location',
        'unit_cost',
        'notes',
        'status',
        'created_by',
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
        'date' => 'date',
        'expiry_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

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
        // New simplified pricing fields
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

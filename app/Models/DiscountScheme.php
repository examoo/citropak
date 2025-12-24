<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountScheme extends Model
{
    protected $fillable = [
        'name',
        'distribution_id',
        'start_date',
        'end_date',
        'scheme_type',
        'product_id',
        'brand_id',
        'from_qty',
        'to_qty',
        'pieces',
        'free_product_code',
        'amount_less',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount_less' => 'decimal:2',
    ];

    /**
     * Get the distribution for this scheme.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Get the product for this scheme.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the brand for this scheme.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Scope to active schemes only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DiscountScheme extends Model
{
    protected $fillable = [
        'name',
        'distribution_id',
        'sub_distribution_id',
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
     * Get the sub distribution for this scheme.
     */
    public function subDistribution(): BelongsTo
    {
        return $this->belongsTo(SubDistribution::class);
    }

    /**
     * Get the single product (legacy/backward compatibility).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the single brand (legacy/backward compatibility).
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get all products for this scheme (many-to-many).
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_scheme_products')
            ->withTimestamps();
    }

    /**
     * Get all brands for this scheme (many-to-many).
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'discount_scheme_brands')
            ->withTimestamps();
    }

    /**
     * Scope to active schemes only.
     * Uses date-only comparison to ensure schemes are active for the entire day.
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();
        return $query->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }
}

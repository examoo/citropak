<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PRODUCT MODEL (GLOBAL)
 * 
 * Shared across all distributions.
 * NO distribution_id - managed only by Super Admin.
 * 
 * NOTE: Distribution-specific stock is managed in stock_ledgers table.
 */
class Product extends Model
{
    protected $fillable = [
        'dms_code',
        'name',
        'brand_id',
        'category_id',
        'type_id',
        'packing_id',
        'list_price_before_tax',
        'fed_tax_percent',
        'fed_sales_tax',
        'net_list_price',
        'distribution_margin',
        'distribution_manager_percent',
        'trade_price_before_tax',
        'fed_2',
        'sales_tax_3',
        'net_trade_price',
        'retailer_margin',
        'retailer_margin_4',
        'consumer_price_before_tax',
        'fed_5',
        'sales_tax_6',
        'net_consumer_price',
        'total_margin',
        'unit_price',
        'reorder_level',
        'stock_quantity',
        'sku',
        'description',
        'price',
        'status',
        // Legacy string columns (kept for backward compatibility)
        'brand',
        'category',
        'type',
        'packing',
        'packing_one',
    ];

    /**
     * Get the brand for this product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the category for this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the packing for this product.
     */
    public function packing(): BelongsTo
    {
        return $this->belongsTo(Packing::class);
    }

    /**
     * Get the type for this product.
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }

    /**
     * Get all variants for this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Scope to active products only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get all stocks for this product.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}

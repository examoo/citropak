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
        'dms_code',           // Code
        'name',               // Product Name
        'brand_id',           // Brand (FK)
        'category_id',        // Category (FK)
        'type_id',            // Types (FK)
        'packing_id',         // Packing (FK)
        'sku',                // SKU
        'list_price_before_tax', // Exclusive Value (Base Price)
        'retail_margin',      // Retail Margin %
        'tp_rate',            // T.P Rate (calculated)
        'distribution_margin', // Distribution Margin %
        'invoice_price',      // Invoice Price (calculated)
        'fed_sales_tax',      // Sale Tax %
        'fed_percent',        // Fed %
        'unit_price',         // Unit Price (Final)
        'reorder_level',      // Reorder Level
        'pieces_per_packing', // Per Packing Piece
        'status',
        // FBR Fields
        'hs_code',            // Harmonized System Code
        'uom_code',           // Unit of Measure Code
    ];

    protected $casts = [
        'list_price_before_tax' => 'decimal:4',
        'retail_margin' => 'decimal:4',
        'tp_rate' => 'decimal:4',
        'distribution_margin' => 'decimal:4',
        'invoice_price' => 'decimal:4',
        'fed_sales_tax' => 'decimal:4',
        'fed_percent' => 'decimal:4',
        'unit_price' => 'decimal:4',
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
        return $query->where(function($q) {
            $q->where('status', 'active')
              ->orWhereNull('status');
        });
    }

    /**
     * Get all stocks for this product.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}

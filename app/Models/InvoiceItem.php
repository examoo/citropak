<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * INVOICE ITEM MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 */
class InvoiceItem extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'invoice_id',
        'product_id',
        'cartons',
        'pieces',
        'total_pieces',
        'quantity',
        'price',
        'discount',
        'scheme_id',
        'scheme_discount',
        'manual_discount_amount',
        'manual_discount_percentage',
        'scheme_discount_amount',
        'tax',
        'tax_percent',
        'fed_percent',
        'fed_amount',
        'extra_tax_percent',
        'extra_tax_amount',
        'adv_tax_percent',
        'adv_tax_amount',
        'exclusive_amount',
        'gross_amount',
        'line_total',
        // New simplified product fields
        'pieces_per_packing',
        'list_price_before_tax',
        'fed_sales_tax',
        'retail_margin',
        'tp_rate',
        'distribution_margin',
        'invoice_price',
        'unit_price',
        'is_free',
        // FBR Fields
        'hs_code',
        'uom_code',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'discount' => 'decimal:4',
        'scheme_discount' => 'decimal:4',
        'manual_discount_amount' => 'decimal:4',
        'manual_discount_percentage' => 'decimal:4',
        'scheme_discount_amount' => 'decimal:4',
        'tax' => 'decimal:4',
        'tax_percent' => 'decimal:4',
        'fed_percent' => 'decimal:4',
        'fed_amount' => 'decimal:4',
        'extra_tax_percent' => 'decimal:4',
        'extra_tax_amount' => 'decimal:4',
        'adv_tax_percent' => 'decimal:4',
        'adv_tax_amount' => 'decimal:4',
        'exclusive_amount' => 'decimal:4',
        'gross_amount' => 'decimal:4',
        'line_total' => 'decimal:4',
        'list_price_before_tax' => 'decimal:4',
        'retail_margin' => 'decimal:4',
        'tp_rate' => 'decimal:4',
        'distribution_margin' => 'decimal:4',
        'invoice_price' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'is_free' => 'boolean',
    ];

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the product (GLOBAL).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the scheme (from discount_schemes table).
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(DiscountScheme::class, 'scheme_id');
    }

    /**
     * Calculate line total.
     */
    public function calculateLineTotal(): float
    {
        $baseAmount = $this->total_pieces * $this->price;
        $afterDiscount = $baseAmount - $this->scheme_discount;
        $withTax = $afterDiscount + $this->tax + $this->fed_amount;
        return $withTax;
    }

    /**
     * Calculate and set all amounts.
     */
    public function calculateAmounts(bool $isDamage = false): void
    {
        $baseAmount = $this->total_pieces * $this->price;
        
        if ($isDamage) {
            // Damage invoice = zero values
            $this->price = 0;
            $this->scheme_discount = 0;
            $this->tax = 0;
            $this->fed_amount = 0;
            $this->extra_tax_amount = 0;
            $this->line_total = 0;
            return;
        }
        
        // Apply scheme discount
        $afterDiscount = $baseAmount - $this->scheme_discount;
        
        // Calculate Tax Amounts based on stored percentages
        // Formula: (value after discount) * percent / 100
        $this->fed_amount = $afterDiscount * ($this->fed_percent / 100);
        $this->tax = $afterDiscount * ($this->tax_percent / 100);
        $this->extra_tax_amount = $afterDiscount * ($this->extra_tax_percent / 100);
        
        $this->line_total = $afterDiscount + $this->tax + $this->fed_amount + $this->extra_tax_amount;
    }
}

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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'scheme_discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'fed_percent' => 'decimal:2',
        'fed_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
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
     * Get the scheme.
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
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

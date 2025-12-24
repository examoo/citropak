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
        'line_total',
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
    public function calculateAmounts(string $taxType = 'food', bool $isDamage = false): void
    {
        $baseAmount = $this->total_pieces * $this->price;
        
        if ($isDamage) {
            // Damage invoice = zero values
            $this->price = 0;
            $this->scheme_discount = 0;
            $this->tax = 0;
            $this->fed_amount = 0;
            $this->line_total = 0;
            return;
        }
        
        // Apply scheme discount
        $afterDiscount = $baseAmount - $this->scheme_discount;
        
        if ($taxType === 'food') {
            // Food: 18% Sales Tax + 4% Extra (if ATL)
            $this->tax_percent = 18;
            $this->fed_percent = 0;
            $this->tax = $afterDiscount * 0.18;
            $this->fed_amount = 0; // Extra tax handled separately based on customer ATL
        } else {
            // Juice: 18% Sales Tax + 20% FED
            $this->tax_percent = 18;
            $this->fed_percent = 20;
            $this->tax = $afterDiscount * 0.18;
            $this->fed_amount = $afterDiscount * 0.20;
        }
        
        $this->line_total = $afterDiscount + $this->tax + $this->fed_amount;
    }
}

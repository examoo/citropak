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
        'quantity',
        'price',
        'discount',
        'tax',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
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
     * Get line total.
     */
    public function getLineTotalAttribute(): float
    {
        return ($this->quantity * $this->price) - $this->discount + $this->tax;
    }
}

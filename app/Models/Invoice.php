<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * INVOICE MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 */
class Invoice extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'order_booker_id',
        'customer_id',
        'invoice_type',
        'tax_type',
        'total_amount',
        'is_credit',
        'invoice_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_credit' => 'boolean',
        'invoice_date' => 'date',
    ];

    /**
     * Get the order booker.
     */
    public function orderBooker(): BelongsTo
    {
        return $this->belongsTo(OrderBooker::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get recoveries.
     */
    public function recoveries(): HasMany
    {
        return $this->hasMany(Recovery::class);
    }

    /**
     * Scope to credit invoices only.
     */
    public function scopeCredit($query)
    {
        return $query->where('is_credit', true);
    }

    /**
     * Scope to sales invoices only.
     */
    public function scopeSales($query)
    {
        return $query->where('invoice_type', 'sale');
    }
}

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
        'invoice_number',
        'van_id',
        'order_booker_id',
        'customer_id',
        'invoice_type',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'fed_amount',
        'total_amount',
        'is_credit',
        'invoice_date',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'fed_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_credit' => 'boolean',
        'invoice_date' => 'date',
    ];

    /**
     * Generate unique invoice number.
     */
    public static function generateInvoiceNumber($distributionId): string
    {
        $date = now()->format('Ymd');
        $prefix = "INV-{$date}-";
        
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}%")
            ->where('distribution_id', $distributionId)
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return $prefix . $nextNumber;
    }

    /**
     * Get the van.
     */
    public function van(): BelongsTo
    {
        return $this->belongsTo(Van::class);
    }

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
     * Get the distribution.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Get the user who created this invoice.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Recalculate totals from items.
     */
    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('line_total');
        $this->discount_amount = $this->items->sum('scheme_discount');
        $this->tax_amount = $this->items->sum('tax');
        $this->fed_amount = $this->items->sum('fed_amount');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount + $this->fed_amount;
        $this->save();
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

    /**
     * Scope to damage invoices only.
     */
    public function scopeDamage($query)
    {
        return $query->where('invoice_type', 'damage');
    }
}

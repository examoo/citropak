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
        'good_issue_note_id', // Linked GIN
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
        // FBR Fields
        'fbr_invoice_number',
        'fbr_pos_id',
        'fbr_qr_code',
        'fbr_response',
        'fbr_status',
        'fbr_synced_at',
        'fbr_error_message',
        // Buyer Information
        'buyer_ntn',
        'buyer_cnic',
        'buyer_name',
        'buyer_phone',
        'buyer_address',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'fed_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_credit' => 'boolean',
        'invoice_date' => 'date',
        'fbr_response' => 'array',
        'fbr_synced_at' => 'datetime',
    ];

    /**
     * Generate unique invoice number.
     * Pattern: Distribution_code-YY-00000 (e.g., DMS-26-00001)
     * Numbers continue sequentially without daily reset.
     */
    public static function generateInvoiceNumber($distributionId): string
    {
        // Get distribution code
        $distribution = Distribution::find($distributionId);
        $distCode = $distribution?->code ?? 'INV';
        
        // Year in 2-digit format
        $year = now()->format('y');
        $prefix = "{$distCode}-{$year}-";
        
        // Find the last invoice for this distribution and year (no daily reset)
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}%")
            ->where('distribution_id', $distributionId)
            ->orderByRaw('CAST(SUBSTRING(invoice_number, -5) AS UNSIGNED) DESC')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -5);
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '00001';
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
     * Get the linked Good Issue Note.
     */
    public function goodIssueNote(): BelongsTo
    {
        return $this->belongsTo(GoodIssueNote::class);
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
        $this->discount_amount = $this->items->sum('discount');
        $this->tax_amount = $this->items->sum('tax');
        $this->fed_amount = $this->items->sum('fed_amount');
        $this->total_amount = $this->subtotal + $this->items->sum('adv_tax_amount');
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

    /**
     * Scope to pending FBR sync invoices.
     */
    public function scopeFbrPending($query)
    {
        return $query->where('fbr_status', 'pending');
    }

    /**
     * Scope to failed FBR sync invoices.
     */
    public function scopeFbrFailed($query)
    {
        return $query->where('fbr_status', 'failed');
    }

    /**
     * Check if invoice is synced with FBR.
     */
    public function isFbrSynced(): bool
    {
        return $this->fbr_status === 'synced' && !empty($this->fbr_invoice_number);
    }

    /**
     * Check if FBR sync is required for this invoice.
     */
    public function requiresFbrSync(): bool
    {
        if (!$this->distribution?->isFbrEnabled()) {
            return false;
        }
        
        return $this->fbr_status !== 'synced' && $this->fbr_status !== 'not_required';
    }

    /**
     * Mark invoice as pending FBR sync.
     */
    public function markFbrPending(): void
    {
        $this->update([
            'fbr_status' => 'pending',
            'fbr_error_message' => null,
        ]);
    }

    /**
     * Mark invoice as synced with FBR.
     */
    public function markFbrSynced(string $fbrInvoiceNumber, ?string $qrCode = null, ?array $response = null): void
    {
        $this->update([
            'fbr_status' => 'synced',
            'fbr_invoice_number' => $fbrInvoiceNumber,
            'fbr_qr_code' => $qrCode,
            'fbr_response' => $response,
            'fbr_synced_at' => now(),
            'fbr_error_message' => null,
        ]);
    }

    /**
     * Mark invoice as failed FBR sync.
     */
    public function markFbrFailed(string $errorMessage, ?array $response = null): void
    {
        $this->update([
            'fbr_status' => 'failed',
            'fbr_error_message' => $errorMessage,
            'fbr_response' => $response,
        ]);
    }

    /**
     * Get the FBR QR code URL for verification.
     */
    public function getFbrVerificationUrl(): ?string
    {
        if (empty($this->fbr_invoice_number)) {
            return null;
        }
        
        return "https://fbr.gov.pk/invoice/{$this->fbr_invoice_number}";
    }

    /**
     * Populate buyer information from customer.
     */
    public function populateBuyerInfo(): void
    {
        if ($this->customer) {
            $this->update([
                'buyer_ntn' => $this->customer->ntn ?? null,
                'buyer_cnic' => $this->customer->cnic ?? null,
                'buyer_name' => $this->customer->shop_name ?? $this->customer->owner_name ?? null,
                'buyer_phone' => $this->customer->mobile_number ?? $this->customer->phone ?? null,
                'buyer_address' => $this->customer->address ?? null,
            ]);
        }
    }
}

<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RECOVERY MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Tracks credit recovery payments.
 */
class Recovery extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'invoice_id',
        'amount',
        'recovery_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'recovery_date' => 'date',
    ];

    /**
     * Get the invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}

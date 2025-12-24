<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GOOD ISSUE NOTE MODEL (DISTRIBUTION-SCOPED)
 * 
 * Tracks goods issued from warehouse to VANs for delivery.
 * Uses BaseTenantModel trait for automatic distribution filtering.
 */
class GoodIssueNote extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'van_id',
        'gin_number',
        'issue_date',
        'status',
        'notes',
        'issued_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    /**
     * Get the van.
     */
    public function van(): BelongsTo
    {
        return $this->belongsTo(Van::class);
    }

    /**
     * Get the user who issued this GIN.
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the distribution.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Get the items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodIssueNoteItem::class);
    }

    /**
     * Generate unique GIN number.
     */
    public static function generateGinNumber($distributionId): string
    {
        $date = now()->format('Ymd');
        $prefix = "GIN-{$date}-";
        
        $lastGin = static::where('gin_number', 'like', "{$prefix}%")
            ->where('distribution_id', $distributionId)
            ->orderBy('gin_number', 'desc')
            ->first();
        
        if ($lastGin) {
            $lastNumber = (int) substr($lastGin->gin_number, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '001';
        }
        
        return $prefix . $nextNumber;
    }

    /**
     * Calculate total amount.
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->items->sum('total_price');
    }

    /**
     * Scope to draft GINs only.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to issued GINs only.
     */
    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }
}

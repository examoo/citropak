<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends Model
{
    use HasFactory, BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'shelf_code',
        'name',
        'status',
        'customer_id',
        'rent_amount',
        'contract_months',
        'start_date',
        'end_date',
        'incentive_amount',
        'order_booker_id',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'incentive_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the customer this shelf is assigned to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order booker.
     */
    public function orderBooker(): BelongsTo
    {
        return $this->belongsTo(OrderBooker::class);
    }

    /**
     * Get monthly records for this shelf.
     */
    public function monthlyRecords(): HasMany
    {
        return $this->hasMany(ShelfMonthlyRecord::class);
    }

    /**
     * Calculate total rent amount.
     */
    public function getTotalAmountAttribute(): float
    {
        return ($this->rent_amount ?? 0) * ($this->contract_months ?? 0);
    }

    /**
     * Generate unique shelf code.
     */
    public static function generateShelfCode($distributionId): string
    {
        $prefix = 'SHL-';
        
        $lastShelf = static::where('distribution_id', $distributionId)
            ->where('shelf_code', 'like', "{$prefix}%")
            ->orderBy('shelf_code', 'desc')
            ->first();
        
        if ($lastShelf) {
            $lastNumber = (int) substr($lastShelf->shelf_code, strlen($prefix));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return $prefix . $nextNumber;
    }

    /**
     * Scope to active shelves only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if shelf is assigned to a customer.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->customer_id);
    }
}

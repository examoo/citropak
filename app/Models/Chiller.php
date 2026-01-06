<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chiller extends Model
{
    use HasFactory, BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'chiller_code',
        'chiller_type_id',
        'name',
        'status',
        'customer_id',
        'order_booker_id',
    ];

    /**
     * Get the customer this chiller is assigned to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the chiller type.
     */
    public function chillerType(): BelongsTo
    {
        return $this->belongsTo(ChillerType::class);
    }

    /**
     * Get the order booker who assigned this chiller.
     */
    public function orderBooker(): BelongsTo
    {
        return $this->belongsTo(OrderBooker::class);
    }

    /**
     * Get movement history for this chiller.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(ChillerMovement::class);
    }

    /**
     * Generate unique chiller code.
     */
    public static function generateChillerCode($distributionId): string
    {
        $prefix = 'CHL-';
        
        $lastChiller = static::where('distribution_id', $distributionId)
            ->where('chiller_code', 'like', "{$prefix}%")
            ->orderBy('chiller_code', 'desc')
            ->first();
        
        if ($lastChiller) {
            $lastNumber = (int) substr($lastChiller->chiller_code, strlen($prefix));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return $prefix . $nextNumber;
    }

    /**
     * Scope to active chillers only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if chiller is assigned to a customer.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->customer_id);
    }
}

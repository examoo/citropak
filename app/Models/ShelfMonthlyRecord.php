<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShelfMonthlyRecord extends Model
{
    use HasFactory, BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'shelf_id',
        'customer_id',
        'month',
        'year',
        'sales_amount',
        'rent_paid',
        'rent_amount',
        'incentive_earned',
        'notes',
    ];

    protected $casts = [
        'sales_amount' => 'decimal:2',
        'rent_amount' => 'decimal:2',
        'incentive_earned' => 'decimal:2',
        'rent_paid' => 'boolean',
    ];

    /**
     * Get the shelf.
     */
    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get month name.
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 
            4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September',
            10 => 'October', 11 => 'November', 12 => 'December'
        ];
        return $months[$this->month] ?? '';
    }

    /**
     * Get period label (e.g., "Jan 2026").
     */
    public function getPeriodLabelAttribute(): string
    {
        $shortMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 
            4 => 'Apr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Aug', 9 => 'Sep',
            10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];
        return ($shortMonths[$this->month] ?? '') . ' ' . $this->year;
    }
}

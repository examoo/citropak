<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChillerMovement extends Model
{
    use HasFactory, BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'chiller_id',
        'from_customer_id',
        'to_customer_id',
        'movement_type',
        'movement_date',
        'order_booker_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    /**
     * Get the chiller.
     */
    public function chiller(): BelongsTo
    {
        return $this->belongsTo(Chiller::class);
    }

    /**
     * Get the source customer (from).
     */
    public function fromCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'from_customer_id');
    }

    /**
     * Get the destination customer (to).
     */
    public function toCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'to_customer_id');
    }

    /**
     * Get the order booker.
     */
    public function orderBooker(): BelongsTo
    {
        return $this->belongsTo(OrderBooker::class);
    }

    /**
     * Get the user who created this movement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

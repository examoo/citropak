<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopVisit extends Model
{
    protected $fillable = [
        'order_booker_id',
        'customer_id',
        'check_in_at',
        'latitude',
        'longitude',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'notes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
    ];

    public function orderBooker(): BelongsTo
    {
        return $this->belongsTo(OrderBooker::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

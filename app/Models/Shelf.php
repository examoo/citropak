<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shelf extends Model
{
    use HasFactory, BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'name',
        'status',
        'customer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    //
}

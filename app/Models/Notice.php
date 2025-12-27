<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;

/**
 * NOTICE MODEL (DISTRIBUTION-SCOPED)
 */
class Notice extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'title',
        'content',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

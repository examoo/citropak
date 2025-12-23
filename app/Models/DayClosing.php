<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DAY CLOSING MODEL (DISTRIBUTION-SCOPED)
 * 
 * Uses BaseTenantModel trait for automatic distribution filtering.
 * Tracks daily business day closings.
 */
class DayClosing extends Model
{
    use BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'date',
        'closed_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the user who closed the day.
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}

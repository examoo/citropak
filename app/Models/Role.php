<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends SpatieRole
{
    /**
     * Get the distribution that owns the role.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChillerType extends Model
{
    use HasFactory, BaseTenantModel;

    protected $fillable = [
        'distribution_id',
        'name',
        'status',
    ];

    /**
     * Get chillers of this type.
     */
    public function chillers(): HasMany
    {
        return $this->hasMany(Chiller::class);
    }

    /**
     * Scope to active types only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

<?php

namespace App\Models\Traits;

use App\Models\Scopes\DistributionScope;
use App\Models\Distribution;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BASE TENANT MODEL TRAIT
 * 
 * Apply this trait to ALL distribution-scoped models.
 * 
 * PROVIDES:
 * 1. Automatic DistributionScope application
 * 2. Distribution relationship
 * 3. Scope bypass methods for Super Admin operations
 * 
 * USAGE:
 * class Customer extends Model
 * {
 *     use BaseTenantModel;
 * }
 * 
 * IMPORTANT RULES:
 * - Every model with distribution_id MUST use this trait
 * - Controllers MUST NOT manually filter by distribution_id
 * - Super Admin bypasses scope automatically
 */
trait BaseTenantModel
{
    /**
     * Boot the trait - applies the DistributionScope.
     */
    public static function bootBaseTenantModel(): void
    {
        static::addGlobalScope(new DistributionScope());
    }

    /**
     * Relationship to distribution.
     */
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    /**
     * Query without distribution scope.
     * Use sparingly - only for Super Admin operations.
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function withoutDistributionScope()
    {
        return static::withoutGlobalScope(DistributionScope::class);
    }
}

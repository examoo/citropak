<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * DISTRIBUTION SCOPE
 * 
 * Global scope that automatically filters queries by distribution_id.
 * Uses SESSION-BASED distribution selection (from topbar switcher).
 * 
 * Session values:
 * - 'all' = Show all distributions (no filter)
 * - [id] = Show only that distribution
 * - null/empty = First visit, use user's default behavior
 */
class DistributionScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Prevent infinite recursion: if we are currently querying the User model 
        // to authenticate the user, Auth::user() would trigger this scope again.
        // Auth::hasUser() checks if the user is already loaded in memory without triggering a fetch.
        if (!Auth::hasUser()) {
            return;
        }

        // Get the authenticated user (safe now as we know it's loaded)
        $user = Auth::user();

        // If no user is authenticated, don't apply scope (redundant check but safe)
        if (!$user) {
            return;
        }

        $sessionValue = session('current_distribution_id');

        // 'all' = user explicitly chose to see all distributions
        if ($sessionValue === 'all') {
            return; // No filter - show all
        }

        // If session has a numeric ID, filter by it
        if ($sessionValue && is_numeric($sessionValue)) {
            $builder->where($model->getTable() . '.distribution_id', $sessionValue);
            return;
        }

        // No session value (first visit) - use default behavior
        // Super Admin (distribution_id = NULL) sees all
        if (is_null($user->distribution_id)) {
            return;
        }

        // Regular users see their own distribution
        $builder->where($model->getTable() . '.distribution_id', $user->distribution_id);
    }
}

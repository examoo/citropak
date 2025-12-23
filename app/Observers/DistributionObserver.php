<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * DISTRIBUTION OBSERVER
 * 
 * Automatically assigns distribution_id when creating records.
 * Register this observer for ALL distribution-scoped models.
 * 
 * BEHAVIOR:
 * - On creating: Sets distribution_id from authenticated user
 * - Super Admin (distribution_id = NULL) must explicitly set distribution_id
 * 
 * USAGE:
 * Register in AppServiceProvider or model's boot method:
 * Customer::observe(DistributionObserver::class);
 */
class DistributionObserver
{
    /**
     * Handle the "creating" event.
     * Auto-fills distribution_id from authenticated user.
     */
    public function creating(Model $model): void
    {
        // Skip if distribution_id is already set
        if ($model->distribution_id) {
            return;
        }

        // Get the authenticated user
        $user = Auth::user();

        // If user is authenticated and has a distribution, assign it
        if ($user && $user->distribution_id) {
            $model->distribution_id = $user->distribution_id;
        }

        // Note: Super Admin (distribution_id = NULL) must explicitly set
        // distribution_id when creating records for a specific distribution
    }
}

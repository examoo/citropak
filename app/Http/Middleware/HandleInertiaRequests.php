<?php

namespace App\Http\Middleware;

use App\Models\Distribution;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Distribution switcher data
            'distributions' => fn () => Distribution::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'currentDistribution' => function () {
                $currentId = session('current_distribution_id');
                
                // 'all' = user explicitly selected All Distributions
                // null/empty = first time visit, default to All for super admin
                if ($currentId === 'all' || !$currentId) {
                    return null; // null means "All Distributions" in frontend
                }
                
                $distribution = Distribution::find($currentId);
                
                return $distribution ? [
                    'id' => $distribution->id,
                    'name' => $distribution->name,
                    'code' => $distribution->code,
                ] : null;
            },
        ];
    }
}

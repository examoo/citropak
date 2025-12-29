<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Services\FbrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * FBR SETTINGS CONTROLLER
 * 
 * Manages FBR integration settings for each distribution.
 * Allows configuring NTN, STRN, POS ID, and API credentials.
 */
class FbrSettingsController extends Controller
{
    public function __construct(private FbrService $fbrService) {}

    /**
     * Display FBR settings for the current distribution.
     */
    /**
     * Display FBR settings for the current distribution.
     */
    public function show(Request $request)
    {
        // Allow overriding via query param, otherwise use session
        $distributionId = $request->query('distribution_id') ?? session('current_distribution_id');
        
        $distributions = Distribution::active()->get(['id', 'name', 'code']);

        if (!$distributionId || $distributionId === 'all') {
            // Render selection view (reusing same component but with null distribution)
            return Inertia::render('Settings/FbrSettings', [
                'distributions' => $distributions,
                'distribution' => null,
                'fbrSettings' => null,
                'uomCodes' => config('fbr.uom_codes'),
            ]);
        }

        $distribution = Distribution::findOrFail($distributionId);
        
        // Mask sensitive data for display
        $fbrSettings = [
            'ntn_number' => $distribution->ntn_number,
            'strn_number' => $distribution->strn_number,
            'business_name' => $distribution->business_name,
            'business_address' => $distribution->business_address,
            'business_phone' => $distribution->business_phone,
            'business_email' => $distribution->business_email,
            'pos_id' => $distribution->pos_id,
            'fbr_username' => $distribution->fbr_username ? '••••••••' : null,
            'fbr_password' => $distribution->fbr_password ? '••••••••' : null,
            'fbr_environment' => $distribution->fbr_environment ?? 'sandbox',
            'fbr_enabled' => $distribution->fbr_enabled,
            'fbr_integration_date' => $distribution->fbr_integration_date?->format('Y-m-d'),
            'is_configured' => $distribution->isFbrEnabled(),
        ];

        return Inertia::render('Settings/FbrSettings', [
            'distributions' => $distributions,
            'distribution' => $distribution->only(['id', 'name', 'code']),
            'fbrSettings' => $fbrSettings,
            'uomCodes' => config('fbr.uom_codes'),
        ]);
    }

    /**
     * Update FBR settings.
     */
    public function update(Request $request)
    {
        $distributionId = $request->input('distribution_id') ?? session('current_distribution_id');
        
        if (!$distributionId || $distributionId === 'all') {
            return redirect()->back()->withErrors(['error' => 'Please select a specific distribution.']);
        }

        $distribution = Distribution::findOrFail($distributionId);

        $validated = $request->validate([
            'ntn_number' => 'nullable|string|max:20',
            'strn_number' => 'nullable|string|max:20',
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email|max:255',
            'pos_id' => 'nullable|string|max:50',
            'fbr_username' => 'nullable|string|max:100',
            'fbr_password' => 'nullable|string|max:255',
            'fbr_environment' => 'required|in:sandbox,production',
            'fbr_enabled' => 'boolean',
        ]);

        // Only update credentials if new ones are provided (not masked values)
        $updateData = [
            'ntn_number' => $validated['ntn_number'],
            'strn_number' => $validated['strn_number'],
            'business_name' => $validated['business_name'],
            'business_address' => $validated['business_address'],
            'business_phone' => $validated['business_phone'],
            'business_email' => $validated['business_email'],
            'pos_id' => $validated['pos_id'],
            'fbr_environment' => $validated['fbr_environment'],
            'fbr_enabled' => $validated['fbr_enabled'] ?? false,
        ];

        // Update username if not masked
        if (!empty($validated['fbr_username']) && $validated['fbr_username'] !== '••••••••') {
            $updateData['fbr_username'] = $validated['fbr_username'];
        }

        // Update password if not masked
        if (!empty($validated['fbr_password']) && $validated['fbr_password'] !== '••••••••') {
            $updateData['fbr_password'] = $validated['fbr_password'];
        }

        // Set integration date if enabling for the first time
        if ($validated['fbr_enabled'] && !$distribution->fbr_enabled) {
            $updateData['fbr_integration_date'] = now();
        }

        $distribution->update($updateData);

        Log::info('FBR Settings Updated', [
            'distribution_id' => $distribution->id,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'FBR settings updated successfully.');
    }

    /**
     * Test FBR API connection.
     */
    public function testConnection(Request $request)
    {
        $distributionId = $request->input('distribution_id') ?? session('current_distribution_id');
        
        if (!$distributionId || $distributionId === 'all') {
            return response()->json([
                'success' => false,
                'message' => 'Please select a specific distribution.',
            ]);
        }

        $distribution = Distribution::findOrFail($distributionId);
        
        $result = $this->fbrService->testConnection($distribution);
        
        return response()->json($result);
    }

    /**
     * Get FBR settings summary for all distributions (admin view).
     */
    public function summary()
    {
        $distributions = Distribution::active()
            ->select([
                'id', 'name', 'code', 
                'ntn_number', 'strn_number',
                'pos_id', 'fbr_enabled', 'fbr_environment',
                'fbr_integration_date'
            ])
            ->get()
            ->map(function ($dist) {
                return [
                    'id' => $dist->id,
                    'name' => $dist->name,
                    'code' => $dist->code,
                    'ntn_number' => $dist->ntn_number,
                    'strn_number' => $dist->strn_number,
                    'pos_id' => $dist->pos_id,
                    'fbr_enabled' => $dist->fbr_enabled,
                    'fbr_environment' => $dist->fbr_environment,
                    'fbr_integration_date' => $dist->fbr_integration_date?->format('Y-m-d'),
                    'is_configured' => $dist->isFbrEnabled(),
                ];
            });

        return Inertia::render('Settings/FbrSummary', [
            'distributions' => $distributions,
        ]);
    }
}

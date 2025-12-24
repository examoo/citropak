<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\OrderBooker;
use App\Models\Van;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderBookerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $bookers = OrderBooker::query()
            ->with(['distribution', 'van']) // Load relations for display
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            // DistributionScope applies automatically via BaseTenantModel.
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('OrderBookers/Index', [
            'bookers' => $bookers,
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name']),
            'vans' => Van::active()->with('distribution')->get(['id', 'code', 'distribution_id']), // Load distribution for display
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Infer distribution_id from user if not provided (e.g. regular user)
        // If super admin (distribution_id is null), they MUST provide it.
        
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'van_id' => 'nullable|exists:vans,id',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId;

        // Manually check uniqueness because unique rule with two columns is complex
        $exists = OrderBooker::where('distribution_id', $distId)
                    ->where('code', $validated['code'])
                    ->exists();
        
        if ($exists) {
            return redirect()->back()->withErrors(['code' => 'The code has already been taken for this distribution.']);
        }

        OrderBooker::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'van_id' => $validated['van_id'] ?? null,
            'distribution_id' => $distId,
        ]);

        return redirect()->back()->with('success', 'Order Booker created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderBooker $orderBooker)
    {
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'van_id' => 'nullable|exists:vans,id',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId ?? $orderBooker->distribution_id;

         // Check uniqueness excluding current record
        $exists = OrderBooker::where('distribution_id', $distId)
                    ->where('code', $validated['code'])
                    ->where('id', '!=', $orderBooker->id)
                    ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['code' => 'The code has already been taken for this distribution.']);
        }

        $orderBooker->update([
             'name' => $validated['name'],
             'code' => $validated['code'],
             'van_id' => $validated['van_id'] ?? null,
             'distribution_id' => $distId,
        ]);

        return redirect()->back()->with('success', 'Order Booker updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderBooker $orderBooker)
    {
        $orderBooker->delete();

        return redirect()->back()->with('success', 'Order Booker deleted successfully.');
    }
}

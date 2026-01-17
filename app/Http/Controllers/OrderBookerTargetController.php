<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Distribution;
use App\Models\OrderBooker;
use App\Models\OrderBookerTarget;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderBookerTargetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $month = $request->query('month');
        
        $targets = OrderBookerTarget::query()
            ->with(['orderBooker.distribution'])
            ->when($search, function($q) use ($search) {
                $q->whereHas('orderBooker', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($month, function($q) use ($month) {
                $q->where('month', $month);
            })
            // DistributionScope applies automatically via BaseTenantModel
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('OrderBookerTargets/Index', [
            'targets' => $targets,
            'orderBookers' => OrderBooker::with('distribution')->get(['id', 'name', 'code', 'distribution_id']),
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name']),
            'brands' => Brand::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'month']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        $validated = $request->validate([
            'order_booker_id' => 'required|exists:order_bookers,id',
            'month' => 'required|string|size:7|regex:/^\d{4}-\d{2}$/',
            'target_amount' => 'required|numeric|min:0',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
            'brand_targets' => 'nullable|array',
            'brand_targets.*' => 'numeric|min:0',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId;

        // Check for existing target with same order_booker + month + distribution
        $exists = OrderBookerTarget::where('distribution_id', $distId)
                    ->where('order_booker_id', $validated['order_booker_id'])
                    ->where('month', $validated['month'])
                    ->exists();
        
        if ($exists) {
            return redirect()->back()->withErrors(['month' => 'A target already exists for this Order Booker in the selected month.']);
        }

        OrderBookerTarget::create([
            'order_booker_id' => $validated['order_booker_id'],
            'month' => $validated['month'],
            'target_amount' => $validated['target_amount'],
            'brand_targets' => $validated['brand_targets'] ?? [],
            'distribution_id' => $distId,
        ]);

        return redirect()->back()->with('success', 'Target created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderBookerTarget $orderBookerTarget)
    {
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        $validated = $request->validate([
            'order_booker_id' => 'required|exists:order_bookers,id',
            'month' => 'required|string|size:7|regex:/^\d{4}-\d{2}$/',
            'target_amount' => 'required|numeric|min:0',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
            'brand_targets' => 'nullable|array',
            'brand_targets.*' => 'numeric|min:0',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId ?? $orderBookerTarget->distribution_id;

        // Check for existing target excluding current record
        $exists = OrderBookerTarget::where('distribution_id', $distId)
                    ->where('order_booker_id', $validated['order_booker_id'])
                    ->where('month', $validated['month'])
                    ->where('id', '!=', $orderBookerTarget->id)
                    ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['month' => 'A target already exists for this Order Booker in the selected month.']);
        }

        $orderBookerTarget->update([
            'order_booker_id' => $validated['order_booker_id'],
            'month' => $validated['month'],
            'target_amount' => $validated['target_amount'],
            'brand_targets' => $validated['brand_targets'] ?? [],
            'distribution_id' => $distId,
        ]);

        return redirect()->back()->with('success', 'Target updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderBookerTarget $orderBookerTarget)
    {
        $orderBookerTarget->delete();

        return redirect()->back()->with('success', 'Target deleted successfully.');
    }
}

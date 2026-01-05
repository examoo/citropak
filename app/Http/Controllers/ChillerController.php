<?php

namespace App\Http\Controllers;

use App\Models\Chiller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChillerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $chillers = Chiller::query()
            ->with(['customer:id,shop_name,customer_code'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($q) use ($search) {
                          $q->where('shop_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                      });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Providing customers for the create/edit modal dropdown
        $customers = Customer::select('id', 'shop_name as name', 'customer_code as code')
            ->where('status', 'active')
            ->orderBy('shop_name')
            ->get();

        return Inertia::render('Chillers/Index', [
            'chillers' => $chillers,
            'filters' => $request->only(['search']),
            'customers' => $customers,
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
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'nullable|exists:customers,id',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
        ]);

        if ($userDistributionId) {
            $validated['distribution_id'] = $userDistributionId;
        }

        Chiller::create($validated);

        return redirect()->back()->with('success', 'Chiller created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chiller $chiller)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $chiller->update($validated);

        return redirect()->back()->with('success', 'Chiller updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chiller $chiller)
    {
        $chiller->delete();

        return redirect()->back()->with('success', 'Chiller deleted successfully.');
    }
}

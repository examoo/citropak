<?php

namespace App\Http\Controllers;

use App\Models\Shelf;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShelfController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $shelves = Shelf::query()
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

        return Inertia::render('Shelves/Index', [
            'shelves' => $shelves,
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

        Shelf::create($validated);

        return redirect()->back()->with('success', 'Shelf created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shelf $shelf)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $shelf->update($validated);

        return redirect()->back()->with('success', 'Shelf updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shelf $shelf)
    {
        $shelf->delete();

        return redirect()->back()->with('success', 'Shelf deleted successfully.');
    }
}

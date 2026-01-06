<?php

namespace App\Http\Controllers;

use App\Models\Shelf;
use App\Models\Customer;
use App\Models\OrderBooker;
use App\Services\ShelfService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShelfController extends Controller
{
    public function __construct(private ShelfService $service)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $shelves = $this->service->getAll($request->only(['search', 'status']));

        $customers = Customer::select('id', 'shop_name as name', 'customer_code as code')
            ->where('status', 'active')
            ->orderBy('shop_name')
            ->get();

        $orderBookers = OrderBooker::select('id', 'name', 'code')->orderBy('name')->get();

        return Inertia::render('Shelves/Index', [
            'shelves' => $shelves,
            'filters' => $request->only(['search', 'status']),
            'customers' => $customers,
            'orderBookers' => $orderBookers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shelf_code' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'nullable|exists:customers,id',
            'rent_amount' => 'nullable|numeric|min:0',
            'contract_months' => 'nullable|integer|min:1|max:120',
            'start_date' => 'nullable|date',
            'incentive_amount' => 'nullable|numeric|min:0',
            'order_booker_id' => 'nullable|exists:order_bookers,id',
        ]);

        // Auto-set distribution_id from session/user
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId && $distributionId !== 'all') {
            $validated['distribution_id'] = $distributionId;
        }

        $this->service->create($validated);

        return redirect()->route('shelves.index')->with('success', 'Shelf created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shelf $shelf)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shelf_code' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'nullable|exists:customers,id',
            'rent_amount' => 'nullable|numeric|min:0',
            'contract_months' => 'nullable|integer|min:1|max:120',
            'start_date' => 'nullable|date',
            'incentive_amount' => 'nullable|numeric|min:0',
            'order_booker_id' => 'nullable|exists:order_bookers,id',
        ]);

        $this->service->update($shelf, $validated);

        return redirect()->route('shelves.index')->with('success', 'Shelf updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shelf $shelf)
    {
        $shelf->delete();

        return redirect()->route('shelves.index')->with('success', 'Shelf deleted successfully.');
    }

    /**
     * Display shelf rent report.
     */
    public function report(Request $request)
    {
        $filters = $request->only(['order_booker_id', 'customer_id', 'year']);
        
        $shelves = $this->service->getShelfReport($filters);

        $orderBookers = OrderBooker::select('id', 'name', 'code')->orderBy('name')->get();
        $customers = Customer::select('id', 'shop_name as name', 'customer_code as code')
            ->where('status', 'active')
            ->orderBy('shop_name')
            ->get();

        // Available years for filter
        $years = range(now()->year - 2, now()->year + 1);

        return Inertia::render('Shelves/Report', [
            'shelves' => $shelves,
            'filters' => $filters,
            'orderBookers' => $orderBookers,
            'customers' => $customers,
            'years' => $years,
            'currentYear' => $filters['year'] ?? now()->year,
        ]);
    }
}

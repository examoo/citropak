<?php

namespace App\Http\Controllers;

use App\Models\OrderBooker;
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
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ob_code', 'like', "%{$search}%")
                  ->orWhere('van', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();



        $vans = \App\Models\Van::where('status', 'active')->latest()->get();

        return Inertia::render('OrderBookers/Index', [
            'bookers' => $bookers,
            'vans' => $vans,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ob_code' => 'required|string|max:50|unique:order_bookers,ob_code',
            'van' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        OrderBooker::create($validated);

        return redirect()->back()->with('success', 'Order Booker created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderBooker $orderBooker)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ob_code' => 'required|string|max:50|unique:order_bookers,ob_code,' . $orderBooker->id,
            'van' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $orderBooker->update($validated);

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

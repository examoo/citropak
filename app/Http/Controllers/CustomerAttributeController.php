<?php

namespace App\Http\Controllers;

use App\Models\CustomerAttribute;
use Illuminate\Http\Request;

class CustomerAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $type = $request->query('type');
        
        $attributes = CustomerAttribute::query()
            ->when($type, fn($q) => $q->where('type', $type))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return \Inertia\Inertia::render('CustomerAttributes/Index', [
            'attributes' => $attributes,
            'type' => $type,
            'filters' => $request->only(['type'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:van,category,channel,distribution,sub_address',
            'value' => 'required|string|max:255'
        ]);

        \App\Models\CustomerAttribute::create($validated);

        return redirect()->back()->with('success', ucfirst($validated['type']) . ' added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerAttribute $customerAttribute)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerAttribute $customerAttribute)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerAttribute $customerAttribute)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255'
        ]);

        $customerAttribute->update($validated);

        return redirect()->back()->with('success', 'Attribute updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerAttribute $customerAttribute)
    {
        $customerAttribute->delete();

        return redirect()->back()->with('success', 'Attribute deleted successfully.');
    }
}

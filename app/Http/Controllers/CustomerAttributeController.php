<?php

namespace App\Http\Controllers;

use App\Models\CustomerAttribute;
use Illuminate\Http\Request;

class CustomerAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerAttribute $customerAttribute)
    {
        //
    }
}

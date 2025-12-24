<?php

namespace App\Http\Controllers;

use App\Models\Packing;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PackingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $packings = Packing::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Packings/Index', [
            'packings' => $packings,
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
            'conversion' => 'required|numeric|min:0.01',
            'units' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $packing = Packing::create($validated);

        return redirect()->back()->with([
            'success' => 'Packing created successfully.',
            'created_packing_id' => $packing->id
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Packing $packing)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'conversion' => 'required|numeric|min:0.01',
            'units' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $packing->update($validated);

        return redirect()->back()->with('success', 'Packing updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Packing $packing)
    {
        $packing->delete();

        return redirect()->back()->with('success', 'Packing deleted successfully.');
    }
}

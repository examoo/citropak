<?php

namespace App\Http\Controllers;

use App\Models\Van;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $vans = Van::query()
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vans/Index', [
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
            'name' => 'required|string|max:255|unique:vans,name',
            'status' => 'required|in:active,inactive',
        ]);

        Van::create($validated);

        return redirect()->back()->with('success', 'Van created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Van $van)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:vans,name,' . $van->id,
            'status' => 'required|in:active,inactive',
        ]);

        $van->update($validated);

        return redirect()->back()->with('success', 'Van updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Van $van)
    {
        $van->delete();

        return redirect()->back()->with('success', 'Van deleted successfully.');
    }
}

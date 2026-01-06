<?php

namespace App\Http\Controllers;

use App\Models\ChillerType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChillerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $chillerTypes = ChillerType::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('ChillerTypes/Index', [
            'chillerTypes' => $chillerTypes,
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
            'status' => 'nullable|in:active,inactive',
        ]);

        // Auto-set distribution_id from session/user
        $validated['distribution_id'] = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($validated['distribution_id'] === 'all') {
            $validated['distribution_id'] = null;
        }
        
        // Default status
        $validated['status'] = $validated['status'] ?? 'active';

        $chillerType = ChillerType::create($validated);

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $chillerType->id,
                'name' => $chillerType->name,
                'message' => 'Chiller Type created successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Chiller Type created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChillerType $chillerType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $chillerType->update($validated);

        return redirect()->back()->with('success', 'Chiller Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChillerType $chillerType)
    {
        $chillerType->delete();

        return redirect()->back()->with('success', 'Chiller Type deleted successfully.');
    }
}

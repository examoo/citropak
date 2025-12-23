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
            ->with(['distribution:id,name,code']) // Eager load
            ->when($search, function($q) use ($search) {
                // If searching by name, but now we have distribution, clarify search?
                // Just search name for now.
                $q->where('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Vans/Index', [
            'vans' => $vans,
            'filters' => $request->only(['search']),
            'distributions' => \App\Models\Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userDist = $request->user()->distribution_id ?? session('current_distribution_id');
        $targetDist = ($userDist && $userDist !== 'all') ? $userDist : $request->input('distribution_id');

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('vans')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                }),
            ],
            'status' => 'required|in:active,inactive',
            'distribution_id' => ($userDist && $userDist !== 'all') ? 'nullable' : 'required|exists:distributions,id',
        ]);

        // Force distribution if scoped
        if ($userDist && $userDist !== 'all') {
            $validated['distribution_id'] = $userDist;
        }

        Van::create($validated);

        return redirect()->back()->with('success', 'Van created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Van $van)
    {
        // For update, uniqueness check should respect the van's distribution (or new one if changing?)
        // Usually we don't change distribution of a van easily.
        // Assuming distribution doesn't change on update, use existing.
        $targetDist = $van->distribution_id;

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('vans')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                })->ignore($van->id),
            ],
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

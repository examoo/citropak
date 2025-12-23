<?php

namespace App\Http\Controllers;

use App\Models\SubDistribution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubDistributionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;
        
        $query = SubDistribution::query()
            ->with(['distribution:id,name,code']);
        
        // If specific distribution, show global + that distribution's sub distributions
        if ($distributionId) {
            $query->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $subDistributions = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('SubDistributions/Index', [
            'subDistributions' => $subDistributions,
            'filters' => $request->only(['search']),
            'distributions' => \App\Models\Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $userDist = $request->user()->distribution_id ?? session('current_distribution_id');
        $targetDist = ($userDist && $userDist !== 'all') ? $userDist : $request->input('distribution_id');

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('sub_distributions')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                }),
            ],
            'is_fbr' => 'required|boolean',
            'status' => 'required|in:active,inactive',
            'distribution_id' => ($userDist && $userDist !== 'all') ? 'nullable' : 'nullable|exists:distributions,id',
        ]);

        // Force distribution if scoped
        if ($userDist && $userDist !== 'all') {
            $validated['distribution_id'] = $userDist;
        } else {
            $validated['distribution_id'] = $request->input('distribution_id') ?: null;
        }

        SubDistribution::create($validated);

        return redirect()->back()->with('success', 'Sub Distribution created successfully.');
    }

    public function update(Request $request, SubDistribution $subDistribution)
    {
        $targetDist = $subDistribution->distribution_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('sub_distributions')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                })->ignore($subDistribution->id),
            ],
            'is_fbr' => 'required|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $subDistribution->update($validated);

        return redirect()->back()->with('success', 'Sub Distribution updated successfully.');
    }

    public function destroy(SubDistribution $subDistribution)
    {
        $subDistribution->delete();

        return redirect()->back()->with('success', 'Sub Distribution deleted successfully.');
    }
}

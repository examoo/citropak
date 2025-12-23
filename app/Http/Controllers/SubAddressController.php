<?php

namespace App\Http\Controllers;

use App\Models\SubAddress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubAddressController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;
        
        $query = SubAddress::query()
            ->with(['distribution:id,name,code']);
        
        // If specific distribution, show global + that distribution's sub addresses
        if ($distributionId) {
            $query->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $subAddresses = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('SubAddresses/Index', [
            'subAddresses' => $subAddresses,
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
                \Illuminate\Validation\Rule::unique('sub_addresses')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                }),
            ],
            'status' => 'required|in:active,inactive',
            'distribution_id' => ($userDist && $userDist !== 'all') ? 'nullable' : 'nullable|exists:distributions,id',
        ]);

        // Force distribution if scoped
        if ($userDist && $userDist !== 'all') {
            $validated['distribution_id'] = $userDist;
        } else {
            $validated['distribution_id'] = $request->input('distribution_id') ?: null;
        }

        SubAddress::create($validated);

        return redirect()->back()->with('success', 'Sub Address created successfully.');
    }

    public function update(Request $request, SubAddress $subAddress)
    {
        $targetDist = $subAddress->distribution_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('sub_addresses')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                })->ignore($subAddress->id),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $subAddress->update($validated);

        return redirect()->back()->with('success', 'Sub Address updated successfully.');
    }

    public function destroy(SubAddress $subAddress)
    {
        $subAddress->delete();

        return redirect()->back()->with('success', 'Sub Address deleted successfully.');
    }
}

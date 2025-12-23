<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChannelController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;
        
        $query = Channel::query()
            ->with(['distribution:id,name,code']);
        
        // If specific distribution, show global + that distribution's channels
        if ($distributionId) {
            $query->where(function($q) use ($distributionId) {
                $q->whereNull('distribution_id')
                  ->orWhere('distribution_id', $distributionId);
            });
        }
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $channels = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Channels/Index', [
            'channels' => $channels,
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
                \Illuminate\Validation\Rule::unique('channels')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                }),
            ],
            'status' => 'required|in:active,inactive',
            'atl' => 'boolean',
            'adv_tax_percent' => 'nullable|numeric|min:0|max:100',
            'distribution_id' => ($userDist && $userDist !== 'all') ? 'nullable' : 'nullable|exists:distributions,id',
        ]);

        // Force distribution if scoped
        if ($userDist && $userDist !== 'all') {
            $validated['distribution_id'] = $userDist;
        } else {
            $validated['distribution_id'] = $request->input('distribution_id') ?: null;
        }

        Channel::create($validated);

        return redirect()->back()->with('success', 'Channel created successfully.');
    }

    public function update(Request $request, Channel $channel)
    {
        $targetDist = $channel->distribution_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('channels')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                })->ignore($channel->id),
            ],
            'status' => 'required|in:active,inactive',
            'atl' => 'boolean',
            'adv_tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $channel->update($validated);

        return redirect()->back()->with('success', 'Channel updated successfully.');
    }

    public function destroy(Channel $channel)
    {
        $channel->delete();

        return redirect()->back()->with('success', 'Channel deleted successfully.');
    }
}

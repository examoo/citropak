<?php

namespace App\Http\Controllers;

use App\Services\HolidayService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function __construct(private HolidayService $service) {}

    public function index(Request $request)
    {
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        return Inertia::render('Holidays/Index', [
            'holidays' => $this->service->getAll($request->only(['month']), $distributionId),
            'filters' => $request->only(['month']),
            'distributions' => \App\Models\Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $userDist = $request->user()->distribution_id ?? session('current_distribution_id');
        $targetDist = ($userDist && $userDist !== 'all') ? $userDist : $request->input('distribution_id');

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'distribution_id' => ($userDist && $userDist !== 'all') ? 'nullable' : 'nullable|exists:distributions,id',
        ]);

        // Force distribution if scoped, otherwise use input (can be null for global holiday)
        if ($userDist && $userDist !== 'all') {
            $validated['distribution_id'] = $userDist;
        } else {
            $validated['distribution_id'] = $request->input('distribution_id') ?: null;
        }

        $this->service->create($validated);

        return redirect()->route('holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string|max:255'
        ]);

        $this->service->update($id, $validated);

        return redirect()->route('holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\RouteService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function __construct(private RouteService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Routes/Index', [
            'routes' => $this->service->getAll($request->only(['search'])),
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
                \Illuminate\Validation\Rule::unique('routes')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                }),
            ],
            'status' => 'nullable|in:active,inactive',
            'distribution_id' => ($userDist && $userDist !== 'all') ? 'nullable' : 'nullable|exists:distributions,id',
        ]);

        // Force distribution if scoped
        if ($userDist && $userDist !== 'all') {
            $validated['distribution_id'] = $userDist;
        } else {
            $validated['distribution_id'] = $request->input('distribution_id') ?: null;
        }

        $validated['status'] = $validated['status'] ?? 'active';

        $this->service->create($validated);

        return redirect()->back()
            ->with('success', 'Route created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $route = \App\Models\Route::findOrFail($id);
        $targetDist = $route->distribution_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                \Illuminate\Validation\Rule::unique('routes')->where(function ($query) use ($targetDist) {
                    return $query->where('distribution_id', $targetDist);
                })->ignore($id),
            ],
            'status' => 'nullable|in:active,inactive',
        ]);

        $this->service->update($id, $validated);

        return redirect()->back()
            ->with('success', 'Route updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('routes.index')
            ->with('success', 'Route deleted successfully.');
    }
}

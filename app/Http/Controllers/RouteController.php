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
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $this->service->create($validated);

        return redirect()->route('routes.index')
            ->with('success', 'Route created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $this->service->update($id, $validated);

        return redirect()->route('routes.index')
            ->with('success', 'Route updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('routes.index')
            ->with('success', 'Route deleted successfully.');
    }
}

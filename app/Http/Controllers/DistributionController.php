<?php

namespace App\Http\Controllers;

use App\Services\DistributionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DistributionController extends Controller
{
    public function __construct(private DistributionService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Distributions/Index', [
            'distributions' => $this->service->getAll($request->only(['search', 'status'])),
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:distributions,code',
            'status' => 'required|in:active,inactive'
        ]);

        $this->service->create($validated);

        return redirect()->route('distributions.index')
            ->with('success', 'Distribution created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:distributions,code,' . $id,
            'status' => 'required|in:active,inactive'
        ]);

        $this->service->update($id, $validated);

        return redirect()->route('distributions.index')
            ->with('success', 'Distribution updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('distributions.index')
            ->with('success', 'Distribution deleted successfully.');
    }

    /**
     * Switch the current active distribution.
     * Stores the selected distribution ID in session.
     * Pass 'all' to clear the filter and show all distributions.
     */
    public function switch(Request $request, string $id)
    {
        // Handle "all" option - store 'all' marker in session
        if ($id === 'all') {
            session(['current_distribution_id' => 'all']);
            return back()->with('success', 'Showing all distributions');
        }

        $distribution = $this->service->find((int) $id);
        
        if (!$distribution) {
            return back()->with('error', 'Distribution not found.');
        }

        // Store in session
        session(['current_distribution_id' => $distribution->id]);

        return back()->with('success', "Switched to {$distribution->name}");
    }
}

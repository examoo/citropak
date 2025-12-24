<?php

namespace App\Http\Controllers;

use App\Services\BrandService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function __construct(private BrandService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Brands/Index', [
            'brands' => $this->service->getAll($request->only(['search', 'status'])),
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        $this->service->create($validated);

        return redirect()->back()
            ->with('success', 'Brand created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        $this->service->update($id, $validated);

        return redirect()->route('brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('brands.index')
            ->with('success', 'Brand deleted successfully.');
    }
}

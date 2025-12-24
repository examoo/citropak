<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'sort_field', 'sort_direction']);

        return Inertia::render('Products/Index', [
            'products' => $this->service->getAll($filters),
            'brands' => \App\Models\Brand::where('status', 'active')->get(['id', 'name']),
            'categories' => \App\Models\ProductCategory::where('status', 'active')->get(['id', 'name']),
            'types' => \App\Models\ProductType::all(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\ProductRequest $request)
    {
        $validated = $request->validated();
        
        // Ensure defaults
        $validated['price'] = $validated['unit_price'] ?? 0;

        $this->service->create($validated);

        return redirect()->back()->with('success', 'Product created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\ProductRequest $request, int $id)
    {
        $validated = $request->validated();
        
        // Ensure defaults
        $validated['price'] = $validated['unit_price'] ?? 0;

        $this->service->update($id, $validated);

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
}

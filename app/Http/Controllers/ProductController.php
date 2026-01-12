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
            'types' => \App\Models\ProductType::all(['id', 'name']),
            'packings' => \App\Models\Packing::where('status', 'active')->get(['id', 'name', 'conversion']),
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
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Product created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\ProductRequest $request, int $id)
    {
        $this->service->update($id, $request->validated());

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);
            return redirect()->back()->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\ProductsImport, $request->file('file'));

        return redirect()->back()->with('success', 'Products imported successfully.');
    }

    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ProductsTemplateExport, 'products_template.xlsx');
    }
}

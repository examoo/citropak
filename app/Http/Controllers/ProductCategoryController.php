<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductCategoryRequest;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function __construct(private ProductCategoryService $service) {}

    /**
     * Display a listing of product categories.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        return Inertia::render('ProductCategories/Index', [
            'categories' => $this->service->getAll($filters),
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created product category.
     */
    public function store(ProductCategoryRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Product Category created successfully.');
    }

    /**
     * Update the specified product category.
     */
    public function update(ProductCategoryRequest $request, ProductCategory $productCategory)
    {
        $this->service->update($productCategory, $request->validated());

        return redirect()->back()->with('success', 'Product Category updated successfully.');
    }

    /**
     * Remove the specified product category.
     */
    public function destroy(ProductCategory $productCategory)
    {
        $this->service->delete($productCategory);

        return redirect()->back()->with('success', 'Product Category deleted successfully.');
    }
}

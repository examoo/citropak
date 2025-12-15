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
            'types' => \App\Models\ProductType::all(),
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dms_code' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'list_price_before_tax' => 'required|numeric|min:0',
            'fed_tax_percent' => 'required|numeric|min:0',
            'fed_sales_tax' => 'required|numeric|min:0',
            'net_list_price' => 'required|numeric|min:0',
            'distribution_margin' => 'nullable|numeric|min:0',
            'distribution_manager_percent' => 'nullable|numeric|min:0',
            'trade_price_before_tax' => 'required|numeric|min:0',
            'fed_2' => 'nullable|numeric|min:0',
            'sales_tax_3' => 'nullable|numeric|min:0',
            'net_trade_price' => 'required|numeric|min:0',
            'retailer_margin' => 'nullable|numeric|min:0',
            'retailer_margin_4' => 'nullable|numeric|min:0',
            'consumer_price_before_tax' => 'required|numeric|min:0',
            'fed_5' => 'nullable|numeric|min:0',
            'sales_tax_6' => 'nullable|numeric|min:0',
            'net_consumer_price' => 'required|numeric|min:0',
            'total_margin' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'packing' => 'nullable|string|max:255',
            'packing_one' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:50|unique:products',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);
        
        // Ensure defaults
        $validated['price'] = $validated['unit_price'] ?? 0;

        $this->service->create($validated);

        return redirect()->back()->with('success', 'Product created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dms_code' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'list_price_before_tax' => 'required|numeric|min:0',
            'fed_tax_percent' => 'required|numeric|min:0',
            'fed_sales_tax' => 'required|numeric|min:0',
            'net_list_price' => 'required|numeric|min:0',
            'distribution_margin' => 'nullable|numeric|min:0',
            'distribution_manager_percent' => 'nullable|numeric|min:0',
            'trade_price_before_tax' => 'required|numeric|min:0',
            'fed_2' => 'nullable|numeric|min:0',
            'sales_tax_3' => 'nullable|numeric|min:0',
            'net_trade_price' => 'required|numeric|min:0',
            'retailer_margin' => 'nullable|numeric|min:0',
            'retailer_margin_4' => 'nullable|numeric|min:0',
            'consumer_price_before_tax' => 'required|numeric|min:0',
            'fed_5' => 'nullable|numeric|min:0',
            'sales_tax_6' => 'nullable|numeric|min:0',
            'net_consumer_price' => 'required|numeric|min:0',
            'total_margin' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'packing' => 'nullable|string|max:255',
            'packing_one' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);
        
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

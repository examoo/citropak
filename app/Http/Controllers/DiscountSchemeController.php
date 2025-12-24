<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountSchemeRequest;
use App\Models\Brand;
use App\Models\DiscountScheme;
use App\Models\Distribution;
use App\Models\Product;
use App\Services\DiscountSchemeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscountSchemeController extends Controller
{
    public function __construct(private DiscountSchemeService $service) {}

    /**
     * Display a listing of discount schemes.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        return Inertia::render('DiscountSchemes/Index', [
            'schemes' => $this->service->getAll($filters, $distributionId),
            'filters' => $filters,
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name', 'code']),
            'products' => Product::all(['id', 'name', 'dms_code']),
            'brands' => Brand::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created discount scheme.
     */
    public function store(DiscountSchemeRequest $request)
    {
        $data = $request->validated();
        
        // Clear irrelevant field based on scheme type
        if ($data['scheme_type'] === 'product') {
            $data['brand_id'] = null;
        } else {
            $data['product_id'] = null;
        }

        $this->service->create($data);

        return redirect()->back()->with('success', 'Discount Scheme created successfully.');
    }

    /**
     * Update the specified discount scheme.
     */
    public function update(DiscountSchemeRequest $request, DiscountScheme $discountScheme)
    {
        $data = $request->validated();
        
        // Clear irrelevant field based on scheme type
        if ($data['scheme_type'] === 'product') {
            $data['brand_id'] = null;
        } else {
            $data['product_id'] = null;
        }

        $this->service->update($discountScheme, $data);

        return redirect()->back()->with('success', 'Discount Scheme updated successfully.');
    }

    /**
     * Remove the specified discount scheme.
     */
    public function destroy(DiscountScheme $discountScheme)
    {
        $this->service->delete($discountScheme);

        return redirect()->back()->with('success', 'Discount Scheme deleted successfully.');
    }
}

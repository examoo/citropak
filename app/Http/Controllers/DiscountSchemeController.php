<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountSchemeRequest;
use App\Models\Brand;
use App\Models\DiscountScheme;
use App\Models\Distribution;
use App\Models\Product;
use App\Models\SubDistribution;
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

        $subDistributions = SubDistribution::when($distributionId, function($q) use ($distributionId) {
            $q->forDistribution($distributionId);
        })->active()->get(['id', 'name', 'distribution_id']);

        return Inertia::render('DiscountSchemes/Index', [
            'schemes' => $this->service->getAll($filters, $distributionId),
            'filters' => $filters,
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name', 'code']),
            'subDistributions' => $subDistributions,
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
        
        // Extract product_ids and brand_ids for syncing
        $productIds = $data['product_ids'] ?? [];
        $brandIds = $data['brand_ids'] ?? [];
        unset($data['product_ids'], $data['brand_ids']);

        $scheme = $this->service->create($data);
        
        // Sync products/brands based on scheme type
        if ($data['scheme_type'] === 'product') {
            $scheme->products()->sync($productIds);
            $scheme->brands()->sync([]);
        } else {
            $scheme->brands()->sync($brandIds);
            $scheme->products()->sync([]);
        }

        return redirect()->back()->with('success', 'Discount Scheme created successfully.');
    }

    /**
     * Update the specified discount scheme.
     */
    public function update(DiscountSchemeRequest $request, DiscountScheme $discountScheme)
    {
        $data = $request->validated();
        
        // Extract product_ids and brand_ids for syncing
        $productIds = $data['product_ids'] ?? [];
        $brandIds = $data['brand_ids'] ?? [];
        unset($data['product_ids'], $data['brand_ids']);

        $this->service->update($discountScheme, $data);
        
        // Sync products/brands based on scheme type
        if ($data['scheme_type'] === 'product') {
            $discountScheme->products()->sync($productIds);
            $discountScheme->brands()->sync([]);
        } else {
            $discountScheme->brands()->sync($brandIds);
            $discountScheme->products()->sync([]);
        }

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

    /**
     * Import discount schemes from Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;
        
        // If user is global, check if they selected a distribution in the form
        if (!$distributionId && $request->has('distribution_id')) {
            $distributionId = $request->distribution_id;
        }

        \Maatwebsite\Excel\Facades\Excel::import(
            new \App\Imports\DiscountSchemesImport($distributionId), 
            $request->file('file')
        );

        return redirect()->back()->with('success', 'Discount Schemes imported successfully.');
    }

    /**
     * Download discount schemes template.
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DiscountSchemesTemplateExport, 
            'discount_schemes_template.xlsx'
        );
    }
}

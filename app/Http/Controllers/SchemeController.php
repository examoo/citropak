<?php

namespace App\Http\Controllers;

use App\Services\SchemeService;
use App\Services\BrandService;
use App\Models\SubDistribution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchemeController extends Controller
{
    public function __construct(
        private SchemeService $service,
        private BrandService $brandService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $subDistributions = SubDistribution::forDistribution($user->distribution_id)
            ->active()
            ->get(['id', 'name']);

        return Inertia::render('Schemes/Index', [
            'schemes' => $this->service->getAll($request->only(['scheme_type', 'is_active'])),
            'brands' => $this->brandService->getActive(),
            'subDistributions' => $subDistributions,
            'filters' => $request->only(['scheme_type', 'is_active'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scheme_type' => 'required|in:brand,product',
            'sub_distribution_id' => 'nullable|exists:sub_distributions,id',
            'brand_id' => 'nullable|exists:brands,id',
            'product_id' => 'nullable|exists:products,id',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $this->service->create($validated);

        return redirect()->route('schemes.index')
            ->with('success', 'Scheme created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'scheme_type' => 'required|in:brand,product',
            'sub_distribution_id' => 'nullable|exists:sub_distributions,id',
            'brand_id' => 'nullable|exists:brands,id',
            'product_id' => 'nullable|exists:products,id',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $this->service->update($id, $validated);

        return redirect()->route('schemes.index')
            ->with('success', 'Scheme updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('schemes.index')
            ->with('success', 'Scheme deleted successfully.');
    }
}

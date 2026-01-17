<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LowStockReportController extends Controller
{
    public function index(Request $request): Response
    {
        $inputDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($inputDistributionId === 'all') $inputDistributionId = null;

        // Determine distributions
        $distributions = $inputDistributionId 
            ? \App\Models\Distribution::where('id', $inputDistributionId)->get() 
            : \App\Models\Distribution::all();

        $filters = $request->only(['search']);

        // Fetch products with reorder_level > 0
        $query = Product::query()
            ->where('reorder_level', '>', 0);
            
        if ($filters['search'] ?? false) {
             $query->where(function($q) use ($filters) {
                 $q->where('name', 'like', '%' . $filters['search'] . '%')
                   ->orWhere('dms_code', 'like', '%' . $filters['search'] . '%');
             });
        }
        
        $products = $query->get();
        $report = [];

        foreach ($products as $product) {
            foreach ($distributions as $distribution) {
                $distributionId = $distribution->id;
                
                $available = Stock::where('product_id', $product->id)
                    ->where('distribution_id', $distributionId)
                    ->sum('quantity');

                if ($available < $product->reorder_level) {
                    $report[] = [
                        'id' => $product->id . '-' . $distributionId,
                        'name' => $product->name,
                        'code' => $product->dms_code,
                        'distribution_name' => $distribution->name,
                        'min_qty' => $product->reorder_level,
                        'available' => $available,
                    ];
                }
            }
        }

        return Inertia::render('LowStockReport/Index', [
            'report' => $report,
            'filters' => $filters,
            'showDistributionColumn' => $inputDistributionId === null // True if "All" selected
        ]);
    }
    public function export(Request $request) 
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\LowStockReportExport($request->all(), $request->user()), 'low-stock-report.xlsx');
    }
}

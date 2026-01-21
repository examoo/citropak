<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\InvoiceItem;
use App\Models\SubDistribution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SubDistributorBrandSalesReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $subDistributionId = $request->input('sub_distribution_id');
        $brandIds = (array) $request->input('brand_ids', []);

        $query = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->where('invoices.invoice_type', 'sale')
            ->whereDate('invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('invoices.invoice_date', '<=', $dateTo)
            ->select(
                'brands.id as brand_id',
                'brands.name as brand_name',
                DB::raw('SUM(invoice_items.total_pieces) as total_quantity'),
                DB::raw('SUM(invoice_items.gross_amount) as total_gross_amount'),
                DB::raw('SUM(invoice_items.discount) as total_discount_amount'),
                DB::raw('SUM(invoice_items.line_total) as total_net_amount'),
                DB::raw('SUM(CASE WHEN invoice_items.is_free = 1 THEN invoice_items.total_pieces ELSE 0 END) as free_quantity')
            )
            ->groupBy('brands.id', 'brands.name');

        if ($subDistributionId) {
            $query->where('customers.sub_distribution_id', $subDistributionId);
        }

        if (!empty($brandIds)) {
            $query->whereIn('brands.id', $brandIds);
        }

        $reportData = $query->get()->map(function ($item) {
            return [
                'brand_id' => $item->brand_id,
                'brand_name' => $item->brand_name,
                'total_quantity' => (int) $item->total_quantity,
                'total_gross_amount' => (float) $item->total_gross_amount,
                'total_discount_amount' => (float) $item->total_discount_amount,
                'total_net_amount' => (float) $item->total_net_amount,
                'free_quantity' => (int) $item->free_quantity,
            ];
        });

        // Totals
        $totals = [
            'quantity' => $reportData->sum('total_quantity'),
            'gross_amount' => $reportData->sum('total_gross_amount'),
            'discount_amount' => $reportData->sum('total_discount_amount'),
            'net_amount' => $reportData->sum('total_net_amount'),
            'free_quantity' => $reportData->sum('free_quantity'),
        ];
        
        // Helper for dropdowns
        $currentDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        $subDistributionsQuery = SubDistribution::query();
        if ($currentDistributionId && $currentDistributionId !== 'all') {
            $subDistributionsQuery->forDistribution($currentDistributionId);
        }
        $subDistributions = $subDistributionsQuery->active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('SubDistributorBrandSalesReport/Index', [
            'reportData' => $reportData,
            'totals' => $totals,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sub_distribution_id' => $subDistributionId,
                'brand_ids' => (array) $brandIds,
            ],
            'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
            'subDistributions' => $subDistributions,
        ]);
    }
}

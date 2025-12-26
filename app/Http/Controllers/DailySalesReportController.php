<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailySalesReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $productTypeId = $request->input('product_type_id');

        // Get products for the selected type (or all if not selected)
        $productsQuery = Product::query()->orderBy('name');
        if ($productTypeId) {
            $productsQuery->where('type_id', $productTypeId);
        }
        $products = $productsQuery->get(['id', 'dms_code', 'name']);

        // Get invoices with items
        $invoices = Invoice::query()
            ->with(['van', 'orderBooker', 'items'])
            ->whereDate('invoice_date', '>=', $dateFrom)
            ->whereDate('invoice_date', '<=', $dateTo)
            ->get();

        // Pivot data: Group by Van + Order Booker
        $reportData = [];
        foreach ($invoices as $invoice) {
            $vanCode = $invoice->van->code ?? 'Unknown';
            $bookerName = $invoice->orderBooker->name ?? 'Unknown';
            $key = $vanCode . '_' . $invoice->order_booker_id;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'van' => $vanCode,
                    'order_booker' => $bookerName,
                    'products' => [],
                ];
                // Initialize all products to 0
                foreach ($products as $product) {
                    $reportData[$key]['products'][$product->id] = 0;
                }
            }

            // Sum quantities per product
            foreach ($invoice->items as $item) {
                if (isset($reportData[$key]['products'][$item->product_id])) {
                    $reportData[$key]['products'][$item->product_id] += $item->total_pieces ?? 0;
                }
            }
        }

        // Convert to array
        $rows = array_values($reportData);

        return Inertia::render('DailySalesReport/Index', [
            'rows' => $rows,
            'products' => $products->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->dms_code,
                'name' => $p->name,
            ])->values(),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'product_type_id' => $productTypeId,
            ],
            'productTypes' => ProductType::orderBy('name')->get(['id', 'name']),
        ]);
    }
}

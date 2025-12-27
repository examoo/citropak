<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class ReorderLiveController extends Controller
{
    public function index(Request $request): Response
    {
        $vanCode = $request->input('van');
        $daysBack = $request->input('days', 30);

        $reorderData = [];

        if ($vanCode) {
            // Get customers for this van
            $customers = Customer::where('van', $vanCode)
                ->where('status', 'active')
                ->get();

            // Get products
            $products = Product::where('status', 'active')
                ->orderBy('dms_code')
                ->get();

            // For each customer, check their last order for each product
            foreach ($customers as $customer) {
                $customerData = [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'shop_name' => $customer->shop_name,
                    'products' => [],
                ];

                foreach ($products as $product) {
                    // Find last invoice with this product for this customer
                    $lastInvoice = Invoice::where('customer_id', $customer->id)
                        ->whereHas('items', function ($q) use ($product) {
                            $q->where('product_id', $product->id);
                        })
                        ->latest('invoice_date')
                        ->first();

                    $daysSinceLastOrder = null;
                    $needsReorder = false;

                    if ($lastInvoice) {
                        $daysSinceLastOrder = now()->diffInDays($lastInvoice->invoice_date);
                        $needsReorder = $daysSinceLastOrder >= $daysBack;
                    } else {
                        $needsReorder = true; // Never ordered
                    }

                    if ($needsReorder) {
                        $customerData['products'][] = [
                            'product_code' => $product->dms_code,
                            'product_name' => $product->name,
                            'days_since' => $daysSinceLastOrder,
                            'last_order_date' => $lastInvoice ? $lastInvoice->invoice_date->format('Y-m-d') : null,
                        ];
                    }
                }

                if (count($customerData['products']) > 0) {
                    $reorderData[] = $customerData;
                }
            }
        }

        // Get vans for filter
        $vans = \App\Models\Van::where('status', 'active')
            ->orderBy('code')
            ->get()
            ->map(fn($v) => ['code' => $v->code, 'name' => $v->code]);

        return Inertia::render('ReorderLive/Index', [
            'reorderData' => $reorderData,
            'vans' => $vans,
            'filters' => [
                'van' => $vanCode,
                'days' => (int) $daysBack,
            ],
        ]);
    }
}

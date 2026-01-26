<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderBooker;
use App\Models\ShopVisit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Channel;
use App\Models\SubDistribution;
use App\Models\Stock;
use App\Models\DiscountScheme;
use App\Models\ProductType;
use App\Http\Resources\Mobile\ProductResource;
use App\Http\Resources\Mobile\CustomerResource;
use App\Http\Resources\Mobile\TargetResource;

class SyncController extends Controller
{
    /**
     * Download Stocks (Dedicated Endpoint)
     */
    public function getStocks(Request $request) 
    {
        $user = $request->user();
        $distributionId = $user->distribution_id;
        
        $stocks = Stock::where('distribution_id', $distributionId)
            ->where('quantity', '>', 0)
            ->get([
                'id', 'product_id', 'batch_number', 'expiry_date', 'quantity', 'unit_cost', 
                'min_quantity', 'max_quantity', 'location', 'notes'
            ]);
            
        return response()->json([
            'stocks' => $stocks,
            'count' => $stocks->count(),
            'sync_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Download Master Data (Down-Sync)
     */
    public function masterData(Request $request)
    {
        $user = $request->user();
        
        // Ensure user is an order booker
        $orderBooker = $user->orderBooker;
        if (!$orderBooker) {
            return response()->json(['error' => 'User is not a valid Order Booker'], 403);
        }

        $distributionId = $user->distribution_id;

        // 1. Products (with Stock for this Distribution)
        $products = Product::with(['productType', 'stocks' => function($q) use ($distributionId) {
                if ($distributionId) {
                    $q->where('distribution_id', $distributionId);
                }
            }])
            ->get();

        // 2. Customers (Scoped to Distribution)
        $customers = Customer::where('distribution_id', $distributionId)
            ->active()
            ->get();

        // 3. Targets (Current Month)
        $currentMonth = now()->format('Y-m');
        $targets = $orderBooker->targets()
            ->latest('month')
            ->first();

        // 4. Reference Data
        $channels = Channel::all();
        $subDistributions = SubDistribution::where('distribution_id', $distributionId)->get();
        
        // 5. Product Types (for Food vs Non-Food calculation)
        $productTypes = ProductType::all(['id', 'name', 'extra_tax']);
        
        // 6. Stocks (for this Distribution)
        $stocks = Stock::where('distribution_id', $distributionId)
            ->where('quantity', '>', 0)
            ->get(['id', 'product_id', 'batch_number', 'expiry_date', 'quantity', 'unit_cost']);
            
        // 7. Discount Schemes (Active and applicable to this Distribution)
        $schemes = DiscountScheme::where(function($q) use ($distributionId) {
                $q->whereNull('sub_distribution_id')
                  ->orWhereIn('sub_distribution_id', 
                      SubDistribution::where('distribution_id', $distributionId)->pluck('id')
                  );
            })
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->with('freeProduct:id,name,dms_code')
            ->get();

        // 8. Customer Brand Percentages (Optimized Fetch)
        $customerIds = $customers->pluck('id');
        $brandDiscounts = \App\Models\CustomerBrandPercentage::whereIn('customer_id', $customerIds)
            ->get(['id', 'customer_id', 'brand_id', 'percentage']);

        return response()->json([
            'meta' => [
                'sync_time' => now()->toIso8601String(),
                'distribution_id' => $distributionId,
            ],
            'products' => ProductResource::collection($products),
            'customers' => CustomerResource::collection($customers),
            'targets' => $targets ? new TargetResource($targets) : null,
            'channels' => $channels,
            'sub_distributions' => $subDistributions,
            'product_types' => $productTypes,
            'stocks' => $stocks,
            'schemes' => $schemes,
            'brand_discounts' => $brandDiscounts,
        ]);
    }

    /**
     * Upload Transactions (Up-Sync)
     */
    public function pushTransactions(Request $request)
    {
        $request->validate([
            'visits' => 'nullable|array',
            'invoices' => 'nullable|array',
        ]);

        $user = $request->user();
        $orderBooker = $user->orderBooker;
        $distributionId = $user->distribution_id;

        if (!$orderBooker) {
            return response()->json(['error' => 'User is not linked to an Order Booker'], 403);
        }

        $syncedIds = [
            'visits' => [],
            'invoices' => [],
            'customers' => [], // Added for customers
        ];

        DB::beginTransaction();
        try {
            // 1. Process New Customers
            if ($request->has('customers')) {
                foreach ($request->customers as $custData) {
                    $customer = Customer::updateOrCreate(
                        ['id' => $custData['server_id'] ?? null], // If server_id exists, update
                        [
                            'shop_name' => $custData['shop_name'],
                            'name' => $custData['owner_name'],
                            'phone' => $custData['phone'],
                            'address' => $custData['address'],
                            'cnic' => $custData['cnic'],
                            'ntn_number' => $custData['ntn'],
                            'lat' => $custData['lat'],
                            'lng' => $custData['lng'],
                            'distribution_id' => $user->distribution_id, // Assign to user's dist
                            'created_by' => $user->id,
                            'status' => 'active',
                            // Admin Parity Fields
                            'channel_id' => $custData['channel_id'] ?? null,
                            'sub_distribution_id' => $custData['sub_distribution_id'] ?? null,
                            'category' => $custData['category'] ?? null,
                            'sub_address' => $custData['sub_address'] ?? null,
                            'contact' => $custData['contact'] ?? null,
                        ]
                    );
                    $syncedIds['customers'][] = $custData['local_id'] ?? $customer->id;
                }
            }

            // 2. Process Visits
            if ($request->has('visits')) {
                foreach ($request->visits as $visitData) {
                    $visit = ShopVisit::create([
                        'user_id' => $user->id, // Added user_id
                        'order_booker_id' => $orderBooker->id,
                        'customer_id' => $visitData['customer_id'],
                        'check_in_at' => $visitData['check_in_at'], // Expecting ISO8601
                        'latitude' => $visitData['latitude'] ?? null,
                        'longitude' => $visitData['longitude'] ?? null,
                        'check_out_at' => $visitData['check_out_at'] ?? null,
                        'check_out_latitude' => $visitData['check_out_latitude'] ?? null,
                        'check_out_longitude' => $visitData['check_out_longitude'] ?? null,
                        'notes' => $visitData['notes'] ?? null,
                    ]);
                    // Store local_id mapped to server_id if needed, or just acknowledge success
                    $syncedIds['visits'][] = $visitData['local_id'] ?? $visit->id;
                }
            }

            // 2. Process Invoices
            if ($request->has('invoices')) {
                foreach ($request->invoices as $invoiceData) {
                    // Generate new Invoice Number
                    $invoiceNumber = Invoice::generateInvoiceNumber($distributionId);

                    $invoice = Invoice::create([
                        'distribution_id' => $distributionId,
                        'invoice_number' => $invoiceNumber,
                        'order_booker_id' => $orderBooker->id,
                        'customer_id' => $invoiceData['customer_id'],
                        'invoice_date' => $invoiceData['invoice_date'] ?? now(),
                        'invoice_type' => 'sale', 
                        'created_by' => $user->id,
                        'van_id' => $orderBooker->van_id,
                        'subtotal' => 0, 
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total_amount' => 0,
                        'notes' => $invoiceData['notes'] ?? null,
                        'is_credit' => $invoiceData['is_credit'] ?? false,
                        // Mobile App specific fields if needed
                        'lat' => $invoiceData['lat'] ?? null,
                        'lng' => $invoiceData['lng'] ?? null,
                    ]);

                    if (!empty($invoiceData['items']) && is_array($invoiceData['items'])) {
                        foreach ($invoiceData['items'] as $itemData) {
                             InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'distribution_id' => $invoice->distribution_id,
                                'product_id' => $itemData['product_id'],
                                'quantity' => $itemData['quantity'], // Total pieces (cartons * packing + pieces)
                                'cartons' => $itemData['cartons'] ?? 0,
                                'pieces' => $itemData['pieces'] ?? 0,
                                'total_pieces' => $itemData['quantity'], // Ensure consistency
                                
                                // Pricing
                                'list_price_before_tax' => $itemData['list_price'] ?? 0, 
                                'price' => $itemData['unit_price'] ?? 0, // Net Unit Price

                                // Amounts
                                'exclusive_amount' => $itemData['exclusive_amount'] ?? 0,
                                'gross_amount' => $itemData['gross_amount'] ?? 0,
                                
                                // Taxes
                                'fed_percent' => $itemData['fed_percent'] ?? 0,
                                'fed_amount' => $itemData['fed_amount'] ?? 0,
                                'tax_percent' => $itemData['gst_percent'] ?? 0,
                                'tax' => $itemData['tax_amount'] ?? 0,
                                'extra_tax_percent' => $itemData['extra_tax_percent'] ?? 0,
                                'extra_tax_amount' => $itemData['extra_tax_amount'] ?? 0,
                                'adv_tax_percent' => $itemData['adv_tax_percent'] ?? 0,
                                'adv_tax_amount' => $itemData['adv_tax_amount'] ?? 0,

                                // Discounts
                                'scheme_id' => $itemData['scheme_id'] ?? null,
                                'scheme_discount' => $itemData['scheme_discount'] ?? 0,
                                'scheme_discount_amount' => $itemData['scheme_discount_amount'] ?? 0,
                                'manual_discount_percentage' => $itemData['manual_discount_percentage'] ?? 0,
                                'manual_discount_amount' => $itemData['manual_discount_amount'] ?? 0,
                                'discount' => $itemData['discount'] ?? 0, // Total Discount usually
                                
                                // Trade Discount / Margin
                                'retail_margin' => $itemData['retail_margin'] ?? 0,
                                'trade_discount_percent' => $itemData['trade_discount_percent'] ?? 0,
                                'trade_discount_amount' => $itemData['trade_discount_amount'] ?? 0,

                                'line_total' => $itemData['line_total'],
                                'total' => $itemData['total'] ?? $itemData['line_total'], 
                                'is_free' => !empty($itemData['is_free']) ? 1 : 0,
                            ]);
                        }
                    }
                    
                    // Recalculate totals
                    $invoice->recalculateTotals();

                    // Populate buyer info
                    $invoice->populateBuyerInfo();

                    // Generate Stock Out (Deduct Stock)
                    // We need to resolve the service instance since we are in a static context or inside a loop
                    app(\App\Services\StockOutService::class)->createFromInvoice($invoice);

                    $syncedIds['invoices'][] = $invoiceData['local_id'] ?? $invoice->id;
                }

            }

            DB::commit();

            return response()->json([
                'success' => true,
                'synced_ids' => $syncedIds,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Log error
            return response()->json(['error' => 'Sync failed: ' . $e->getMessage()], 500);
        }
    }
}

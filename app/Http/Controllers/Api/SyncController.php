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
use App\Http\Resources\Mobile\ProductResource;
use App\Http\Resources\Mobile\CustomerResource;
use App\Http\Resources\Mobile\TargetResource;

class SyncController extends Controller
{
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
        // Note: Assuming stock logic is handled via Stocks relationship or similar.
        // For now, loading active products.
        $products = Product::with(['stocks' => function($q) use ($distributionId) {
                // Filter stocks by distribution if relation allows, 
                // or load all and filter in Resource if needed.
                // Assuming 'stocks' has distribution_id logic or we take all.
            }])
            ->get();

        // 2. Customers (Scoped to Distribution)
        $customers = Customer::where('distribution_id', $distributionId)
            ->active()
            ->get();

        // 3. Targets (Current Month)
        $currentMonth = now()->format('Y-m');
        $targets = $orderBooker->targets()
            // ->where('month', $currentMonth) // Depending on date format in DB
            ->latest('month') // Get latest target for now
            ->first();

        // 4. Reference Data
        $channels = Channel::all(); // Fetch all channels for now
        $subDistributions = SubDistribution::where('distribution_id', $distributionId)->get();

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
                        'invoice_type' => 'sale', // Default to sale
                        'created_by' => $user->id,
                        'van_id' => $orderBooker->van_id,
                        'subtotal' => 0, // Will recalculate
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total_amount' => 0,
                        'notes' => $invoiceData['notes'] ?? null,
                        'is_credit' => $invoiceData['is_credit'] ?? false,
                    ]);

                    if (!empty($invoiceData['items']) && is_array($invoiceData['items'])) {
                        foreach ($invoiceData['items'] as $itemData) {
                             InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'product_id' => $itemData['product_id'],
                                'quantity' => $itemData['quantity'],
                                'unit_price' => $itemData['unit_price'],
                                'line_total' => $itemData['line_total'], // Trusted from app or recalculate? 
                                // Ideally recalculate unit_price * quantity
                                'discount' => $itemData['discount'] ?? 0,
                                'tax' => $itemData['tax'] ?? 0,
                                'total' => $itemData['total'],
                            ]);
                        }
                    }
                    
                    // Recalculate totals to ensure accuracy
                    $invoice->recalculateTotals();

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

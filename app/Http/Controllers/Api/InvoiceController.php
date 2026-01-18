<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\StockOutService; // Ensure this service exists and is injectable
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct(
        private StockOutService $stockOutService
    ) {}

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'nullable|integer|min:0', // Total pieces? or carton/pieces separate?
            // Assuming simplified input for API: straight quantities
        ]);

        $user = $request->user();
        $orderBooker = $user->orderBooker;

        if (!$orderBooker) {
            return response()->json(['message' => 'User is not an Order Booker.'], 403);
        }

        return DB::transaction(function () use ($request, $user, $orderBooker) {
            // Generate Invoice Number
            $invoiceNumber = Invoice::generateInvoiceNumber($user->distribution_id);

            $invoice = Invoice::create([
                'distribution_id' => $user->distribution_id,
                'invoice_number' => $invoiceNumber,
                'order_booker_id' => $orderBooker->id,
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'invoice_type' => 'sale', // Default to sale
                'created_by' => $user->id,
                'van_id' => $orderBooker->van_id, 
                'notes' => $request->notes,
                // Location data if provided
                // 'latitude' => $request->lat,
                // 'longitude' => $request->lng,
            ]);

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                
                // Logic to handle quantity (cartons/pieces) if sent separately
                // For now assuming 'quantity' is total pieces or handled by caller
                // If 'quantity_carton' and 'quantity_pieces' are sent:
                $qtyCarton = $itemData['quantity_carton'] ?? 0;
                $qtyPieces = $itemData['quantity_pieces'] ?? 0;
                $totalQty = ($qtyCarton * $product->pieces_per_packing) + $qtyPieces;
                
                if ($totalQty == 0 && isset($itemData['quantity'])) {
                    $totalQty = $itemData['quantity'];
                }

                // Calculate prices
                // Assuming backend calculation for security
                $price = $product->invoice_price; // or unit_price? Using invoice_price as per standard
                $lineTotal = $price * $totalQty;
                
                // Handle discounts/schemes if passed or calculate them?
                // For MVP, we might accept calculated values or calculate basic here.
                // Assuming basic calculation:
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $totalQty, 
                    // 'quantity_carton' => $qtyCarton, // if column exists
                    // 'quantity_pieces' => $qtyPieces, // if column exists
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                    'discount' => 0, // Implement scheme logic if needed
                    'tax' => 0,
                    'total' => $lineTotal,
                ]);
            }

            $invoice->recalculateTotals();

            // Auto-create StockOut/GIN? 
            // In web flow, this acts as an Order. 
            // If we want to reserve stock immediately:
            // $this->stockOutService->createFromInvoice($invoice); (Hypothetical method)
            
            // For now, just return the invoice created.
            
            return response()->json([
                'message' => 'Invoice created successfully.',
                'invoice' => $invoice->load('items'),
            ], 201);
        });
    }
}

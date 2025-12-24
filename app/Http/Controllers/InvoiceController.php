<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Distribution;
use App\Models\Holiday;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderBooker;
use App\Models\Product;
use App\Models\Scheme;
use App\Models\Van;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $bookerId = $request->query('booker_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        $invoices = Invoice::query()
            ->with(['van', 'orderBooker', 'customer', 'distribution', 'items'])
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->when($type, fn($q) => $q->where('invoice_type', $type))
            ->when($bookerId, fn($q) => $q->where('order_booker_id', $bookerId))
            ->when($dateFrom, fn($q) => $q->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('invoice_date', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'orderBookers' => OrderBooker::with('distribution')->get(['id', 'name', 'code', 'distribution_id']),
            'filters' => $request->only(['search', 'type', 'booker_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userDistributionId = auth()->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        return Inertia::render('Invoices/Create', [
            'vans' => Van::active()->with('distribution')->get(),
            'orderBookers' => OrderBooker::with(['distribution', 'van'])->get(),
            'products' => Product::active()->with(['brand', 'category', 'packing'])->get(),
            'schemes' => Scheme::active()->with(['brand', 'product'])->get(),
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name']),
            'nextOrderDate' => $this->getNextOrderDate($userDistributionId),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userDistributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        $validated = $request->validate([
            'van_id' => 'required|exists:vans,id',
            'order_booker_id' => 'required|exists:order_bookers,id',
            'customer_id' => 'required|exists:customers,id',
            'invoice_type' => 'required|in:sale,damage,shelf_rent',
            'tax_type' => 'required|in:food,juice',
            'invoice_date' => 'required|date',
            'is_credit' => 'boolean',
            'notes' => 'nullable|string',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cartons' => 'required|integer|min:0',
            'items.*.pieces' => 'required|integer|min:0',
            'items.*.total_pieces' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.scheme_id' => 'nullable|exists:schemes,id',
            'items.*.scheme_discount' => 'nullable|numeric|min:0',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId;
        $isDamage = $validated['invoice_type'] === 'damage';

        DB::beginTransaction();
        try {
            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber($distId);

            // Create invoice
            $invoice = Invoice::create([
                'distribution_id' => $distId,
                'invoice_number' => $invoiceNumber,
                'van_id' => $validated['van_id'],
                'order_booker_id' => $validated['order_booker_id'],
                'customer_id' => $validated['customer_id'],
                'invoice_type' => $validated['invoice_type'],
                'tax_type' => $validated['tax_type'],
                'invoice_date' => $validated['invoice_date'],
                'is_credit' => $validated['is_credit'] ?? false,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create items
            foreach ($validated['items'] as $itemData) {
                $item = new InvoiceItem([
                    'distribution_id' => $distId,
                    'invoice_id' => $invoice->id,
                    'product_id' => $itemData['product_id'],
                    'cartons' => $itemData['cartons'],
                    'pieces' => $itemData['pieces'],
                    'total_pieces' => $itemData['total_pieces'],
                    'quantity' => $itemData['total_pieces'],
                    'price' => $itemData['price'],
                    'scheme_id' => $itemData['scheme_id'] ?? null,
                    'scheme_discount' => $itemData['scheme_discount'] ?? 0,
                ]);
                
                // Calculate taxes based on type
                $item->calculateAmounts($validated['tax_type'], $isDamage);
                $item->save();
            }

            // Recalculate totals
            $invoice->recalculateTotals();

            DB::commit();
            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Invoice created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'van', 
            'orderBooker', 
            'customer', 
            'distribution', 
            'createdBy',
            'items.product.brand', 
            'items.product.packing',
            'items.scheme'
        ]);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load([
            'van', 
            'orderBooker', 
            'customer', 
            'distribution',
            'items.product.brand', 
            'items.product.packing',
            'items.scheme'
        ]);

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'products' => Product::active()->with(['brand', 'category', 'packing'])->get(),
            'schemes' => Scheme::active()->with(['brand', 'product'])->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'invoice_type' => 'required|in:sale,damage,shelf_rent',
            'tax_type' => 'required|in:food,juice',
            'is_credit' => 'boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cartons' => 'required|integer|min:0',
            'items.*.pieces' => 'required|integer|min:0',
            'items.*.total_pieces' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.scheme_id' => 'nullable|exists:schemes,id',
            'items.*.scheme_discount' => 'nullable|numeric|min:0',
        ]);

        $isDamage = $validated['invoice_type'] === 'damage';

        DB::beginTransaction();
        try {
            // Update invoice
            $invoice->update([
                'invoice_type' => $validated['invoice_type'],
                'tax_type' => $validated['tax_type'],
                'is_credit' => $validated['is_credit'] ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Get existing item IDs
            $existingItemIds = collect($validated['items'])->pluck('id')->filter()->toArray();
            
            // Delete removed items
            $invoice->items()->whereNotIn('id', $existingItemIds)->delete();

            // Update or create items
            foreach ($validated['items'] as $itemData) {
                $item = isset($itemData['id']) 
                    ? InvoiceItem::find($itemData['id'])
                    : new InvoiceItem(['distribution_id' => $invoice->distribution_id, 'invoice_id' => $invoice->id]);

                $item->fill([
                    'product_id' => $itemData['product_id'],
                    'cartons' => $itemData['cartons'],
                    'pieces' => $itemData['pieces'],
                    'total_pieces' => $itemData['total_pieces'],
                    'quantity' => $itemData['total_pieces'],
                    'price' => $itemData['price'],
                    'scheme_id' => $itemData['scheme_id'] ?? null,
                    'scheme_discount' => $itemData['scheme_discount'] ?? 0,
                ]);
                
                $item->calculateAmounts($validated['tax_type'], $isDamage);
                $item->save();
            }

            // Recalculate totals
            $invoice->refresh();
            $invoice->recalculateTotals();

            DB::commit();
            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', 'Invoice updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update invoice: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Get next order date (skip holidays).
     */
    private function getNextOrderDate($distributionId): string
    {
        $date = Carbon::tomorrow();
        $maxDays = 10; // Prevent infinite loop
        
        while ($maxDays > 0) {
            $isHoliday = Holiday::query()
                ->where('date', $date->toDateString())
                ->where(function($q) use ($distributionId) {
                    $q->whereNull('distribution_id')
                      ->orWhere('distribution_id', $distributionId);
                })
                ->exists();
            
            if (!$isHoliday) {
                return $date->toDateString();
            }
            
            $date->addDay();
            $maxDays--;
        }
        
        return Carbon::tomorrow()->toDateString();
    }

    // ===========================================
    // API ENDPOINTS FOR CASCADING DROPDOWNS
    // ===========================================

    /**
     * Get order bookers by VAN.
     */
    public function getOrderBookersByVan($vanId)
    {
        $bookers = OrderBooker::where('van_id', $vanId)
            ->with('distribution')
            ->get(['id', 'name', 'code', 'distribution_id', 'van_id']);
        
        return response()->json($bookers);
    }

    /**
     * Get customers by order booker and day.
     */
    public function getCustomersByBooker(Request $request, $bookerId)
    {
        $day = $request->query('day');
        $booker = OrderBooker::find($bookerId);
        
        if (!$booker) {
            return response()->json([]);
        }

        // Get the VAN code from the booker's van
        $vanCode = $booker->van?->code;

        $customers = Customer::query()
            ->where('van', $vanCode)
            ->where('status', 'active')
            ->when($day, fn($q) => $q->where('day', $day))
            ->orderBy('shop_name')
            ->get();
        
        return response()->json($customers);
    }

    /**
     * Get customer by code.
     */
    public function getCustomerByCode($code)
    {
        $customer = Customer::where('customer_code', $code)->first();
        
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }
        
        return response()->json($customer);
    }

    /**
     * Get product by code.
     */
    public function getProductByCode($code)
    {
        $product = Product::where('dms_code', $code)
            ->orWhere('sku', $code)
            ->with(['brand', 'category', 'packing'])
            ->first();
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        
        return response()->json($product);
    }

    /**
     * Get schemes for a product.
     */
    public function getSchemesForProduct($productId)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return response()->json([]);
        }

        // Get schemes for this product or its brand
        $schemes = Scheme::active()
            ->where(function($q) use ($product) {
                $q->where('product_id', $product->id)
                  ->orWhere('brand_id', $product->brand_id);
            })
            ->with(['brand', 'product'])
            ->get();
        
        return response()->json($schemes);
    }

    /**
     * Get next order date API.
     */
    public function getNextOrderDateApi(Request $request)
    {
        $distributionId = $request->query('distribution_id') ?? session('current_distribution_id');
        if($distributionId === 'all') $distributionId = null;
        
        return response()->json([
            'date' => $this->getNextOrderDate($distributionId)
        ]);
    }
}

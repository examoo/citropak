<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\Product;
use App\Models\StockOut;
use App\Services\StockOutService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Stock;

class StockOutController extends Controller
{
    public function __construct(private StockOutService $service) {}

    /**
     * Display a listing of stock outs.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        // Get all stocks (even if quantity is 0, so user can see them)
        $stockQuery = Stock::with(['product', 'distribution']);
        
        if ($distributionId) {
            $stockQuery->where('distribution_id', $distributionId);
        }

        $availableStocks = $stockQuery->get()->map(function ($stock) {
            return [
                'id' => $stock->id,
                'name' => $stock->product->name . ' (Qty: ' . $stock->quantity . ')',
                'product_id' => $stock->product_id,
                'distribution_id' => $stock->distribution_id,
                'quantity' => $stock->quantity,
                'product' => $stock->product,
                'batch_number' => $stock->batch_number,
                'expiry_date' => $stock->expiry_date,
                'location' => $stock->location,
                'unit_cost' => $stock->unit_cost,
            ];
        });

        return Inertia::render('StockOuts/Index', [
            'stockOuts' => $this->service->getAll($filters, $distributionId),
            'filters' => $filters,
            'availableStocks' => $availableStocks,
            'products' => Product::all(['id', 'name', 'dms_code']), // Restore products list, optimized
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created stock out.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'distribution_id' => 'required|exists:distributions,id',
            'bilty_number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.stock_id' => 'required|exists:stocks,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.location' => 'nullable|string',
            'items.*.unit_cost' => 'nullable|numeric',
        ]);

        $data = [
            'distribution_id' => $validated['distribution_id'],
            'bilty_number' => $validated['bilty_number'] ?? null,
            'date' => $validated['date'],
            'remarks' => $validated['remarks'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ];

        try {
            $this->service->create($data, $validated['items']);
            return redirect()->back()->with('success', 'Stock Out created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified stock out.
     */
    public function update(Request $request, StockOut $stockOut)
    {
        if ($stockOut->status === 'posted') {
            return redirect()->back()->with('error', 'Cannot edit a posted stock out.');
        }

        $validated = $request->validate([
            'distribution_id' => 'required|exists:distributions,id',
            'bilty_number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.stock_id' => 'required|exists:stocks,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.location' => 'nullable|string',
            'items.*.unit_cost' => 'nullable|numeric',
        ]);

        $data = [
            'distribution_id' => $validated['distribution_id'],
            'bilty_number' => $validated['bilty_number'] ?? null,
            'date' => $validated['date'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        try {
            $this->service->update($stockOut, $data, $validated['items']);
            return redirect()->back()->with('success', 'Stock Out updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Post/Confirm stock out and update stocks.
     */
    public function post(StockOut $stockOut)
    {
        if ($stockOut->status === 'posted') {
            return redirect()->back()->with('error', 'Stock Out is already posted.');
        }

        try {
            $this->service->post($stockOut);
            return redirect()->back()->with('success', 'Stock Out posted and stocks updated.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified stock out.
     */
    public function destroy(StockOut $stockOut)
    {
        try {
            $this->service->delete($stockOut);
            return redirect()->back()->with('success', 'Stock Out deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

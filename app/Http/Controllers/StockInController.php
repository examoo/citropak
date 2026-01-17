<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\Product;
use App\Models\StockIn;
use App\Services\StockInService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockInController extends Controller
{
    public function __construct(private StockInService $service) {}

    /**
     * Display a listing of stock ins.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $distributionId = $request->user()->distribution_id ?? session('current_distribution_id');
        if ($distributionId === 'all') $distributionId = null;

        return Inertia::render('StockIns/Index', [
            'stockIns' => $this->service->getAll($filters, $distributionId),
            'filters' => $filters,
            'products' => Product::all(),
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created stock in.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'distribution_id' => 'required|exists:distributions,id',
            'bilty_number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
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
            'status' => 'posted',
            'created_by' => $request->user()->id,
        ];

        $this->service->create($data, $validated['items']);

        return redirect()->back()->with('success', 'Stock In created successfully.');
    }

    /**
     * Update the specified stock in.
     */
    public function update(Request $request, StockIn $stockIn)
    {
        // Posted check removed to allow editing (service handles stock updates)


        $validated = $request->validate([
            'distribution_id' => 'required|exists:distributions,id',
            'bilty_number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
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

        $this->service->update($stockIn, $data, $validated['items']);

        return redirect()->back()->with('success', 'Stock In updated successfully.');
    }

    /**
     * Post/Confirm stock in and update stocks.
     */
    public function post(StockIn $stockIn)
    {
        if ($stockIn->status === 'posted') {
            return redirect()->back()->with('error', 'Stock In is already posted.');
        }

        $this->service->post($stockIn);

        return redirect()->back()->with('success', 'Stock In posted and stocks updated.');
    }

    /**
     * Remove the specified stock in.
     */
    public function destroy(StockIn $stockIn)
    {
        try {
            $this->service->delete($stockIn);
            return redirect()->back()->with('success', 'Stock In deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

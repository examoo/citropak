<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\GoodIssueNote;
use App\Models\GoodIssueNoteItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Van;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GoodIssueNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $vanId = $request->query('van_id');
        
        $gins = GoodIssueNote::query()
            ->with(['van', 'issuedBy', 'distribution', 'items'])
            ->when($search, function($q) use ($search) {
                $q->where('gin_number', 'like', "%{$search}%");
            })
            ->when($status, function($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($vanId, function($q) use ($vanId) {
                $q->where('van_id', $vanId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('GoodIssueNotes/Index', [
            'gins' => $gins,
            'vans' => Van::active()->with('distribution')->get(['id', 'code', 'distribution_id']),
            'filters' => $request->only(['search', 'status', 'van_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userDistributionId = auth()->user()->distribution_id ?? session('current_distribution_id');
        if($userDistributionId === 'all') $userDistributionId = null;

        return Inertia::render('GoodIssueNotes/Create', [
            'vans' => Van::active()->with('distribution')->get(['id', 'code', 'distribution_id']),
            'products' => Product::active()->with(['brand', 'category', 'packing'])->get(),
            'stocks' => Stock::where('quantity', '>', 0)
                ->when($userDistributionId, fn($q) => $q->where('distribution_id', $userDistributionId))
                ->with('product')
                ->get(),
            'distributions' => Distribution::where('status', 'active')->get(['id', 'name']),
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
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
            'distribution_id' => $userDistributionId ? 'nullable' : 'required|exists:distributions,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.stock_id' => 'nullable|exists:stocks,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $distId = $request->distribution_id ?? $userDistributionId;

        DB::beginTransaction();
        try {
            // Generate GIN number
            $ginNumber = GoodIssueNote::generateGinNumber($distId);

            // Create GIN
            $gin = GoodIssueNote::create([
                'distribution_id' => $distId,
                'van_id' => $validated['van_id'],
                'gin_number' => $ginNumber,
                'issue_date' => $validated['issue_date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'issued_by' => auth()->id(),
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                GoodIssueNoteItem::create([
                    'good_issue_note_id' => $gin->id,
                    'product_id' => $item['product_id'],
                    'stock_id' => $item['stock_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();
            return redirect()->route('good-issue-notes.show', $gin->id)
                ->with('success', 'Good Issue Note created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create GIN: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GoodIssueNote $goodIssueNote)
    {
        $goodIssueNote->load(['van', 'issuedBy', 'distribution', 'items.product.brand', 'items.product.packing', 'items.stock']);

        return Inertia::render('GoodIssueNotes/Show', [
            'gin' => $goodIssueNote,
        ]);
    }

    /**
     * Issue the GIN (deduct stock).
     */
    public function issue(GoodIssueNote $goodIssueNote)
    {
        if ($goodIssueNote->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Only draft GINs can be issued.']);
        }

        DB::beginTransaction();
        try {
            // Deduct stock for each item
            foreach ($goodIssueNote->items as $item) {
                if ($item->stock_id) {
                    $stock = Stock::find($item->stock_id);
                    if ($stock && $stock->quantity >= $item->quantity) {
                        $stock->decrement('quantity', $item->quantity);
                    } else {
                        throw new \Exception("Insufficient stock for product: {$item->product->name}");
                    }
                }
            }

            // Update status
            $goodIssueNote->update(['status' => 'issued']);

            DB::commit();
            return redirect()->back()->with('success', 'GIN issued successfully. Stock has been deducted.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel the GIN.
     */
    public function cancel(GoodIssueNote $goodIssueNote)
    {
        if ($goodIssueNote->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Only draft GINs can be cancelled.']);
        }

        $goodIssueNote->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'GIN cancelled successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GoodIssueNote $goodIssueNote)
    {
        if ($goodIssueNote->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Only draft GINs can be deleted.']);
        }

        $goodIssueNote->delete();

        return redirect()->route('good-issue-notes.index')
            ->with('success', 'Good Issue Note deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Recovery;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecoveryController extends Controller
{
    /**
     * Display a listing of recoveries.
     */
    public function index(Request $request): Response
    {
        $query = Recovery::with(['invoice.customer', 'invoice.van']);

        // Filters
        if ($request->filled('date_from')) {
            $query->whereDate('recovery_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('recovery_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('customer', function ($cq) use ($request) {
                        $cq->where('shop_name', 'like', '%' . $request->search . '%')
                            ->orWhere('customer_code', 'like', '%' . $request->search . '%');
                    });
            });
        }

        $recoveries = $query->latest('recovery_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($rec) => [
                'id' => $rec->id,
                'invoice_number' => $rec->invoice->invoice_number ?? '-',
                'customer_code' => $rec->invoice->customer->customer_code ?? '-',
                'customer_name' => $rec->invoice->customer->shop_name ?? '-',
                'van' => $rec->invoice->van->code ?? '-',
                'amount' => (float) $rec->amount,
                'recovery_date' => $rec->recovery_date->format('Y-m-d'),
            ]);

        $totalRecovered = $query->sum('amount');

        return Inertia::render('Recoveries/Index', [
            'recoveries' => $recoveries,
            'totalRecovered' => (float) $totalRecovered,
            'filters' => $request->only(['date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Store a new recovery.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'recovery_date' => 'required|date',
        ]);

        // Get distribution_id from the invoice
        $invoice = \App\Models\Invoice::findOrFail($validated['invoice_id']);

        Recovery::create([
            'distribution_id' => $invoice->distribution_id,
            'invoice_id' => $validated['invoice_id'],
            'amount' => $validated['amount'],
            'recovery_date' => $validated['recovery_date'],
        ]);

        return back()->with('success', 'Recovery recorded successfully.');
    }
}

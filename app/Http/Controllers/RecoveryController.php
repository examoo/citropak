<?php

namespace App\Http\Controllers;

use App\Models\Recovery;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class RecoveryController extends Controller
{
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

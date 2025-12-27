<?php

namespace App\Http\Controllers;

use App\Models\CreditBooker;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CreditBookerController extends Controller
{
    public function index(): Response
    {
        $bookers = CreditBooker::orderBy('name')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'code' => $b->code ?? '-',
                'name' => $b->name,
                'phone' => $b->phone ?? '-',
                'status' => $b->status,
            ]);

        return Inertia::render('CreditBookers/Index', [
            'bookers' => $bookers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        CreditBooker::create($validated);

        return redirect()->back()->with('success', 'Credit Booker created successfully.');
    }

    public function update(Request $request, CreditBooker $creditBooker): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        $creditBooker->update($validated);

        return redirect()->back()->with('success', 'Credit Booker updated successfully.');
    }

    public function destroy(CreditBooker $creditBooker): RedirectResponse
    {
        $creditBooker->delete();

        return redirect()->back()->with('success', 'Credit Booker deleted successfully.');
    }
}

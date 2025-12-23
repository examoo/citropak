<?php

namespace App\Http\Controllers;

use App\Services\HolidayService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function __construct(private HolidayService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Holidays/Index', [
            'holidays' => $this->service->getAll($request->only(['month'])),
            'filters' => $request->only(['month'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string|max:255'
        ]);

        $this->service->create($validated);

        return redirect()->route('holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string|max:255'
        ]);

        $this->service->update($id, $validated);

        return redirect()->route('holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}

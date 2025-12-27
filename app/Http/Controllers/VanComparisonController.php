<?php

namespace App\Http\Controllers;

use App\Models\Van;
use App\Services\VanComparisonService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VanComparisonController extends Controller
{
    public function __construct(private VanComparisonService $comparisonService)
    {
    }

    public function index(Request $request): Response
    {
        $van1Id = $request->input('van1_id');
        $van2Id = $request->input('van2_id');
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        $comparisonData = [
            'van1' => null,
            'van2' => null,
            'customers' => [],
            'totals' => ['van1' => 0, 'van2' => 0],
        ];

        if ($van1Id && $van2Id) {
            $comparisonData = $this->comparisonService->getComparisonData(
                (int) $van1Id,
                (int) $van2Id,
                (int) $month,
                (int) $year
            );
        }

        // Generate years for dropdown (current year and 5 years back)
        $currentYear = (int) date('Y');
        $years = [];
        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
            $years[] = $i;
        }

        return Inertia::render('VanComparison/Index', [
            'van1' => $comparisonData['van1'],
            'van2' => $comparisonData['van2'],
            'customers' => $comparisonData['customers'],
            'totals' => $comparisonData['totals'],
            'filters' => [
                'van1_id' => $van1Id,
                'van2_id' => $van2Id,
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'vans' => Van::select('id', 'code')
                ->orderBy('code')
                ->get()
                ->map(fn($v) => [
                    'id' => $v->id,
                    'code' => $v->code,
                    'name' => $v->code,
                ]),
            'years' => $years,
        ]);
    }
}

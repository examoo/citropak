<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Product;
use App\Models\OpeningStock;
use App\Models\StockInItem;
use App\Models\StockOutItem;
use App\Models\ClosingStock;
use App\Models\Stock;
use App\Models\Distribution;
use Carbon\Carbon;

class StockReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;
    protected $user;

    public function __construct($filters, $user)
    {
        $this->filters = $filters;
        $this->user = $user;
    }

    public function collection()
    {
        $month = $this->filters['month'] ?? now()->format('Y-m');
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();
        
        $inputDistributionId = $this->user->distribution_id ?? session('current_distribution_id');
        if ($inputDistributionId === 'all') $inputDistributionId = null;

        $distributions = $inputDistributionId 
            ? Distribution::where('id', $inputDistributionId)->get() 
            : Distribution::all();

        $search = $this->filters['search'] ?? '';
        $products = Product::query();
        if ($search) {
             $products->where('name', 'like', '%' . $search . '%')
                 ->orWhere('dms_code', 'like', '%' . $search . '%');
        }
        $products = $products->get();

        $report = [];

        foreach ($products as $product) {
            foreach ($distributions as $distribution) {
                $distributionId = $distribution->id;

                // Previous month dates for opening stock calculation
                $prevMonthEnd = $startDate->copy()->subDay()->endOfDay();
                
                // Opening Stock Logic
                $opening = OpeningStock::where('product_id', $product->id)
                    ->whereDate('date', $startDate)
                    ->where('distribution_id', $distributionId)
                    ->value('quantity');
                
                if ($opening === null) {
                    $prevClosing = ClosingStock::where('product_id', $product->id)
                        ->whereDate('date', $prevMonthEnd)
                        ->where('distribution_id', $distributionId)
                        ->value('quantity');
                    
                    if ($prevClosing !== null) {
                        $opening = $prevClosing;
                    }
                }

                if ($opening === null) {
                    $currentStock = Stock::where('product_id', $product->id)
                        ->where('distribution_id', $distributionId)
                        ->sum('quantity');
                    
                    $totalInSinceStart = StockInItem::where('product_id', $product->id)
                        ->whereHas('stockIn', function ($q) use ($startDate, $distributionId) {
                            $q->where('date', '>=', $startDate)
                              ->where('distribution_id', $distributionId);
                        })
                        ->sum('quantity');
                    
                    $totalOutSinceStart = StockOutItem::where('product_id', $product->id)
                        ->whereHas('stockOut', function ($q) use ($startDate, $distributionId) {
                            $q->where('date', '>=', $startDate)
                              ->where('distribution_id', $distributionId);
                        })
                        ->sum('quantity');
                    
                    $opening = $currentStock - $totalInSinceStart + $totalOutSinceStart;
                }

                $opening = $opening ?? 0;

                $in = StockInItem::where('product_id', $product->id)
                    ->whereHas('stockIn', function ($q) use ($startDate, $endDate, $distributionId) {
                        $q->whereBetween('date', [$startDate, $endDate])
                          ->where('distribution_id', $distributionId);
                    })
                    ->sum('quantity');

                $out = StockOutItem::where('product_id', $product->id)
                    ->whereHas('stockOut', function ($q) use ($startDate, $endDate, $distributionId) {
                        $q->whereBetween('date', [$startDate, $endDate])
                          ->where('distribution_id', $distributionId);
                    })
                    ->sum('quantity');

                $closingRaw = ClosingStock::where('product_id', $product->id)
                    ->whereDate('date', $endDate)
                    ->where('distribution_id', $distributionId)
                    ->first();
                
                $closing = $closingRaw ? $closingRaw->quantity : null;
                $isCurrentMonth = $month === now()->format('Y-m');

                // Calculate closing if not explicitly saved
                if ($closing === null && !$isCurrentMonth) {
                    $closing = $opening + $in - $out;
                }
                
                // If it's current month, use current stock as closing for display if not closed
                if ($closing === null && $isCurrentMonth) {
                     $closing = Stock::where('product_id', $product->id)
                        ->where('distribution_id', $distributionId)
                        ->sum('quantity');
                }

                // Skip empty rows
                if ($opening == 0 && $in == 0 && $out == 0 && $closing == 0) {
                    continue;
                }

                $report[] = [
                    'product_name' => $product->name,
                    'product_code' => $product->dms_code,
                    'distribution_name' => $distribution->name,
                    'opening' => $opening,
                    'in' => $in,
                    'out' => $out,
                    'closing' => $closing,
                ];
            }
        }

        return collect($report);
    }

    public function map($row): array
    {
        return [
            $row['product_code'],
            $row['product_name'],
            $row['distribution_name'],
            $row['opening'],
            $row['in'],
            $row['out'],
            $row['closing'],
        ];
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Distribution',
            'Opening',
            'In',
            'Out',
            'Closing',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Distribution;

class LowStockReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        $inputDistributionId = $this->user->distribution_id ?? session('current_distribution_id');
        if ($inputDistributionId === 'all') $inputDistributionId = null;

        $distributions = $inputDistributionId 
            ? Distribution::where('id', $inputDistributionId)->get() 
            : Distribution::all();

        $query = Product::query()->where('reorder_level', '>', 0);
        $search = $this->filters['search'] ?? '';

        if ($search) {
             $query->where(function($q) use ($search) {
                 $q->where('name', 'like', '%' . $search . '%')
                   ->orWhere('dms_code', 'like', '%' . $search . '%');
             });
        }
        
        $products = $query->get();
        $report = [];

        foreach ($products as $product) {
            foreach ($distributions as $distribution) {
                $distributionId = $distribution->id;
                
                $available = Stock::where('product_id', $product->id)
                    ->where('distribution_id', $distributionId)
                    ->sum('quantity');

                if ($available < $product->reorder_level) {
                    $report[] = [
                        'name' => $product->name,
                        'code' => $product->dms_code,
                        'distribution_name' => $distribution->name,
                        'min_qty' => $product->reorder_level,
                        'available' => $available,
                    ];
                }
            }
        }

        return collect($report);
    }

    public function map($row): array
    {
        return [
            $row['code'],
            $row['name'],
            $row['distribution_name'],
            $row['min_qty'],
            $row['available'],
        ];
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Distribution',
            'Min Qty',
            'Available Stock',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

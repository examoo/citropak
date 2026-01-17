<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Services\ShelfService;

class ShelfReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $service = app(ShelfService::class);
        return $service->getShelfReport($this->filters);
    }

    public function map($shelf): array
    {
        $row = [
            $shelf->shelf_code,
            $shelf->name,
            $shelf->customer->customer_code ?? '-',
            $shelf->customer->shop_name ?? '-',
            $shelf->orderBooker->name ?? '-',
        ];

        // Add monthly sales columns
        if (isset($shelf->monthly_sales)) {
            for ($m = 1; $m <= 12; $m++) {
                $row[] = $shelf->monthly_sales[$m] ?? 0;
            }
        } else {
             // Fallback if not set (should not happen with getShelfReport)
             for ($m = 1; $m <= 12; $m++) {
                $row[] = 0;
             }
        }

        $row[] = $shelf->total_sales;

        return $row;
    }

    public function headings(): array
    {
        $headings = [
            'Shelf Code',
            'Shelf Name',
            'Customer Code',
            'Shop Name',
            'Order Booker',
        ];

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($months as $month) {
            $headings[] = $month;
        }

        $headings[] = 'Total Sales';

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

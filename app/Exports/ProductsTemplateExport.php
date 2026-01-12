<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class ProductsTemplateExport implements WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'product_code',
            'product_name',
            'brand',
            'sku',
            'exclusive_value',
            'tp_rate',
            'invoice_price',
            'unit_price',
            'retail_margin',
            'distribution_margin',
            'sale_tax',
            'fed',
            'product_type',
            'reorder_level',
            'packing',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
            ],
        ];
    }
}

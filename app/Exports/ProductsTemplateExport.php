<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductsTemplateExport implements WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'Product_code',
            'Product_Name',
            'Brand',
            'Sku',
            'Exclusive Value',
            'T.P Rate',
            'Invoice Price',
            'Unit Price',
            'Retail Margin',
            'Distribution Margin',
            'Sale Tax',
            'FED',
            'Product Type',
            'Re-Order Level'
        ];
    }
}

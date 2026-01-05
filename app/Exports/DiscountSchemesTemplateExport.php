<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class DiscountSchemesTemplateExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Return empty collection to create just headers
        return new Collection([]);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Distribution',
            'SubDistribution',
            'SchemeType',
            'ProductCode',
            'BrandName',
            'StartDate',
            'EndDate',
            'FromQty',
            'ToQty',
            'Pieces',
            'FreeProductCode',
            'AmountLess',
            'Status',
        ];
    }
}

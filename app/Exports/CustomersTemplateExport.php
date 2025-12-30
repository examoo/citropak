<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class CustomersTemplateExport implements FromCollection, WithHeadings
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
            'CustomerCode',
            'Van',
            'ShopName',
            'Address',
            'SubAddress',
            'Telephone',
            'Categories',
            'Channel',
            'NTNNumber',
            'CNIC',
            'FilerType', // Replacing ambiguous second "Categories"
            'ST Status',
            'ATL Status',
            'Distribution',
            'Adv_Tax',
            'Day',
            'Percentage',
        ];
    }
}

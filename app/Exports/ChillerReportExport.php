<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Services\ChillerService;
use Illuminate\Support\Collection;

class ChillerReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Resolve service manually since we can't easily inject into Excel export constructor from controller cleanly via new
        $service = app(ChillerService::class);
        return $service->getChillerReport($this->filters);
    }

    public function map($chiller): array
    {
        return [
            $chiller->chiller_code,
            $chiller->name,
            $chiller->chillerType->name ?? '-',
            $chiller->customer->customer_code ?? '-',
            $chiller->customer->shop_name ?? '-',
            $chiller->customer->address ?? '-',
            $chiller->orderBooker->name ?? '-',
            $chiller->total_sale,
        ];
    }

    public function headings(): array
    {
        return [
            'Chiller Code',
            'Chiller Name',
            'Type',
            'Customer Code',
            'Shop Name',
            'Address',
            'Order Booker',
            'Total Sale',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

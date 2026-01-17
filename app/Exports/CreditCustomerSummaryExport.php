<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;

class CreditCustomerSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $month = $this->filters['month'] ?? date('n');
        $year = $this->filters['year'] ?? date('Y');

        $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        return Invoice::where('is_credit', true)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['customer', 'recoveries'])
            ->get()
            ->groupBy('customer_id')
            ->map(function ($invoices) {
                $customer = $invoices->first()->customer;
                $totalCredit = $invoices->sum('total_amount');
                $totalRecovered = $invoices->flatMap->recoveries->sum('amount');
                
                return [
                    'customer_code' => $customer->customer_code ?? '-',
                    'customer_name' => $customer->shop_name ?? '-',
                    'total_credit' => $totalCredit,
                    'total_recovered' => $totalRecovered,
                    'pending' => $totalCredit - $totalRecovered,
                ];
            })
            ->values();
    }

    public function map($row): array
    {
        return [
            $row['customer_code'],
            $row['customer_name'],
            $row['total_credit'],
            $row['total_recovered'],
            $row['pending'],
        ];
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Customer Name',
            'Total Credit',
            'Total Recovered',
            'Pending Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

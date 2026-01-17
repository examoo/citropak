<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;

class CreditBillSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Invoice::where('is_credit', true)
            ->with(['customer', 'van', 'recoveries']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $this->filters['date_to']);
        }

        return $query->latest('invoice_date')->get();
    }

    public function map($inv): array
    {
        $recovered = $inv->recoveries->sum('amount');
        return [
            $inv->invoice_date->format('Y-m-d'),
            $inv->invoice_number,
            $inv->customer->customer_code ?? '-',
            $inv->customer->shop_name ?? '-',
            $inv->van->code ?? '-',
            (float) $inv->total_amount,
            (float) $recovered,
            (float) $inv->total_amount - $recovered,
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'Invoice #',
            'Customer Code',
            'Customer Name',
            'Van',
            'Amount',
            'Recovered',
            'Pending',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

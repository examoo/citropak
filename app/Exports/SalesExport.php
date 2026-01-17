<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Invoice::query()
            ->with(['customer', 'van', 'orderBooker']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $this->filters['date_to']);
        }
        if (!empty($this->filters['van_id'])) {
            $query->where('van_id', $this->filters['van_id']);
        }
        if (!empty($this->filters['order_booker_id'])) {
            $query->where('order_booker_id', $this->filters['order_booker_id']);
        }

        return $query->latest('invoice_date')->get();
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_date->format('d-m-Y'),
            $invoice->invoice_number,
            $invoice->orderBooker->name ?? '-',
            $invoice->van->code ?? '-',
            $invoice->customer->customer_code ?? '-',
            $invoice->customer->shop_name ?? '-',
            $invoice->is_credit ? 'Credit' : 'Cash',
            $invoice->total_amount,
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'Invoice No',
            'Order Booker',
            'Van',
            'Customer Code',
            'Shop Name',
            'Type',
            'Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

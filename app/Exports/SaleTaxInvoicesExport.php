<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;

class SaleTaxInvoicesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Invoice::query()
            ->with(['customer'])
            ->whereYear('invoice_date', $this->year)
            ->whereMonth('invoice_date', $this->month)
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->get();
    }

    public function map($invoice): array
    {
        $buyerName = $invoice->customer->shop_name ?? $invoice->customer_name ?? 'Walk-in Customer';
        $buyerNtn = $invoice->customer->ntn_number ?? $invoice->customer->cnic ?? '';
        $taxableValue = $invoice->subtotal - $invoice->discount_amount; // Net Taxable

        return [
            $invoice->invoice_date->format('d-m-Y'),
            $invoice->invoice_number,
            $invoice->customer->customer_code ?? '',
            $buyerName,
            $invoice->customer->address ?? '',
            $invoice->customer->phone ?? '',
            $invoice->customer->ntn_number ?? '', // NTN
            $invoice->customer->cnic ?? '', // CNIC
            $invoice->customer->sales_tax_number ?? '', // STN
            $invoice->subtotal,
            $invoice->discount_amount,
            $taxableValue,
            $invoice->tax_amount,
            $invoice->fed_amount,
            $invoice->total_amount,
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'Invoice No',
            'Code',
            'Buyer Name',
            'Address',
            'Phone',
            'NTN',
            'CNIC',
            'STN',
            'Gross Amount',
            'Discount',
            'Value Excl. Tax',
            'Sales Tax',
            'Further Tax',
            'Value Incl. Tax',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}

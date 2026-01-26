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
            ->withSum('items', 'extra_tax_amount')
            ->withSum('items', 'adv_tax_amount')
            ->withSum('items', 'retail_margin')
            ->withSum('items', 'exclusive_amount')
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
        
        $exclValue = $invoice->items_sum_exclusive_amount ?? 0;
        $tradeDiscount = $invoice->items_sum_retail_margin ?? 0;
        $extraTax = $invoice->items_sum_extra_tax_amount ?? 0;
        $advTax = $invoice->items_sum_adv_tax_amount ?? 0;
        $grossAmount = $exclValue + $invoice->tax_amount + $invoice->fed_amount + $extraTax;

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
            $exclValue,
            $invoice->fed_amount,
            $invoice->tax_amount,
            $extraTax,
            $grossAmount,
            $tradeDiscount,
            $invoice->discount_amount, // Scheme Disc
            $invoice->subtotal, // Net Value
            $advTax,
            $invoice->total_amount, // Total Value
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
            'Excl. Value',
            'FED',
            'Sales Tax',
            'Extra Tax',
            'Gross Amount',
            'Trade Disc.',
            'Scheme Disc.',
            'Net Value',
            'Adv. Tax',
            'Total Value',
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

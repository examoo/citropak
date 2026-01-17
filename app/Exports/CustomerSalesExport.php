<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;
use App\Models\Customer;

class CustomerSalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $customerCode = $this->filters['customer_code'] ?? null;
        
        if (!$customerCode) {
            return collect([]);
        }

        $customer = Customer::where('customer_code', $customerCode)->first();

        if (!$customer) {
            return collect([]);
        }

        $query = Invoice::query()
            ->with(['van'])
            ->where('customer_id', $customer->id);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('invoice_date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('invoice_date', '<=', $this->filters['date_to']);
        }

        return $query->latest('invoice_date')->get();
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->invoice_date->format('d-m-Y'),
            $invoice->van->code ?? '-',
            $invoice->subtotal + $invoice->discount_amount, // Gross
            $invoice->discount_amount,
            $invoice->subtotal, // After Discount
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Date',
            'Van',
            'Gross Sale',
            'Discount',
            'After Discount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

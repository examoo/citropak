<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $search = $this->filters['search'] ?? null;
        $type = $this->filters['type'] ?? null;
        $bookerId = $this->filters['booker_id'] ?? null;
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;

        $query = Invoice::query()
            ->with(['van', 'orderBooker', 'customer', 'distribution', 'createdBy']);

        if ($search) {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        if ($type) {
            $query->where('invoice_type', $type);
        }

        if ($bookerId) {
            $query->where('order_booker_id', $bookerId);
        }

        if ($dateFrom) {
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }

        return $query->latest('invoice_date')->get();
    }

    public function map($invoice): array
    {
        return [
            $invoice->id,
            $invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : '-',
            $invoice->invoice_number,
            $invoice->distribution->name ?? '-',
            $invoice->van->code ?? '-',
            $invoice->orderBooker->name ?? '-',
            $invoice->customer->shop_name ?? '-',
            $invoice->customer->customer_code ?? '-',
            ucfirst(str_replace('_', ' ', $invoice->invoice_type)),
            $invoice->is_credit ? 'Credit' : 'Cash',
            $invoice->subtotal,
            $invoice->discount_amount,
            $invoice->tax_amount,
            $invoice->fed_amount,
            $invoice->total_amount,
            $invoice->buyer_name ?? '-',
            $invoice->buyer_ntn ?? '-',
            $invoice->buyer_cnic ?? '-',
            $invoice->buyer_phone ?? '-',
            $invoice->buyer_address ?? '-',
            $invoice->fbr_invoice_number ?? '-',
            ucfirst($invoice->fbr_status ?? 'N/A'),
            $invoice->createdBy->name ?? '-',
            $invoice->notes ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Invoice #',
            'Distribution',
            'Van Code',
            'Order Booker',
            'Customer Name',
            'Customer Code',
            'Type',
            'Payment Type',
            'Subtotal',
            'Discount',
            'Tax',
            'FED',
            'Total Amount',
            'Buyer Name',
            'Buyer NTN',
            'Buyer CNIC',
            'Buyer Phone',
            'Buyer Address',
            'FBR Invoice #',
            'FBR Status',
            'Created By',
            'Notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

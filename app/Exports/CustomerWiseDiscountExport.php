<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class CustomerWiseDiscountExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $dateFrom = $this->filters['date_from'] ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $this->filters['date_to'] ?? now()->endOfMonth()->format('Y-m-d');
        $customerIds = $this->filters['customer_ids'] ?? [];

        $query = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->whereDate('invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('invoices.invoice_date', '<=', $dateTo)
            ->where('invoices.invoice_type', 'sale')
            ->select(
                'customers.customer_code',
                'customers.shop_name as customer_name',
                DB::raw('SUM(invoice_items.gross_amount) as total_gross_amount'),
                DB::raw('SUM(invoice_items.discount) as total_discount_amount'),
                DB::raw('SUM(invoice_items.line_total) as total_net_amount'),
                DB::raw('SUM(CASE WHEN invoice_items.is_free = 1 THEN invoice_items.total_pieces ELSE 0 END) as free_quantity')
            )
            ->groupBy('invoices.customer_id', 'customers.customer_code', 'customers.shop_name');

        if (!empty($customerIds)) {
            $query->whereIn('invoices.customer_id', $customerIds);
        }

        return $query->get();
    }

    public function map($row): array
    {
        return [
            $row->customer_code,
            $row->customer_name,
            $row->total_gross_amount,
            $row->total_discount_amount,
            $row->total_net_amount,
            (int) $row->free_quantity,
        ];
    }

    public function headings(): array
    {
        return [
            'Customer Code',
            'Shop Name',
            'Total Gross Amount',
            'Total Discount',
            'Total Net Amount',
            'Free Quantity (Pcs)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

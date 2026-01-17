<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class BrandWiseSalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
        $brandIds = $this->filters['brand_ids'] ?? [];

        $query = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->where('invoices.invoice_type', 'sale')
            ->whereDate('invoices.invoice_date', '>=', $dateFrom)
            ->whereDate('invoices.invoice_date', '<=', $dateTo)
            ->select(
                'brands.name as brand_name',
                DB::raw('SUM(invoice_items.total_pieces) as total_quantity'),
                DB::raw('SUM(invoice_items.gross_amount) as total_gross_amount'),
                DB::raw('SUM(invoice_items.discount) as total_discount_amount'),
                DB::raw('SUM(invoice_items.line_total) as total_net_amount'),
                DB::raw('SUM(CASE WHEN invoice_items.is_free = 1 THEN invoice_items.total_pieces ELSE 0 END) as free_quantity')
            )
            ->groupBy('brands.id', 'brands.name');

        if (!empty($brandIds)) {
            $query->whereIn('brands.id', $brandIds);
        }

        return $query->get();
    }

    public function map($row): array
    {
        return [
            $row->brand_name,
            $row->total_quantity,
            $row->total_gross_amount,
            $row->total_discount_amount,
            $row->total_net_amount,
            $row->free_quantity,
        ];
    }

    public function headings(): array
    {
        return [
            'Brand',
            'Quantity',
            'Gross Amount',
            'Discount',
            'Net Amount',
            'Free Quantity',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

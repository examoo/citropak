<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;
use App\Models\Product;

class DailySalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;
    protected $products;

    public function __construct($filters)
    {
        $this->filters = $filters;
        // Fetch products to define columns (consistent with controller logic)
        $productsQuery = Product::query()->orderBy('name');
        if (!empty($filters['product_type_id'])) {
            $productsQuery->where('type_id', $filters['product_type_id']);
        }
        $this->products = $productsQuery->get();
    }

    public function collection()
    {
        $dateFrom = $this->filters['date_from'] ?? now()->format('Y-m-d');
        $dateTo = $this->filters['date_to'] ?? now()->format('Y-m-d');

        // Get invoices with items
        $invoices = Invoice::query()
            ->with(['van', 'orderBooker', 'items'])
            ->whereDate('invoice_date', '>=', $dateFrom)
            ->whereDate('invoice_date', '<=', $dateTo)
            ->get();

        // Pivot data
        $reportData = [];
        foreach ($invoices as $invoice) {
            $vanCode = $invoice->van->code ?? 'Unknown';
            $bookerName = $invoice->orderBooker->name ?? 'Unknown';
            $key = $vanCode . '_' . $invoice->order_booker_id;

            if (!isset($reportData[$key])) {
                $reportData[$key] = [
                    'van' => $vanCode,
                    'order_booker' => $bookerName,
                    'products' => [],
                ];
                // Initialize all products to 0
                foreach ($this->products as $product) {
                    $reportData[$key]['products'][$product->id] = 0;
                }
            }

            foreach ($invoice->items as $item) {
                if (isset($reportData[$key]['products'][$item->product_id])) {
                    $reportData[$key]['products'][$item->product_id] += $item->total_pieces ?? 0;
                }
            }
        }
        
        return collect(array_values($reportData));
    }

    public function map($row): array
    {
        $mapped = [
            $row['van'],
            $row['order_booker'],
        ];

        // Add quantity for each product column
        foreach ($this->products as $product) {
            $mapped[] = $row['products'][$product->id] ?? 0;
        }

        return $mapped;
    }

    public function headings(): array
    {
        $headings = ['Van', 'Order Booker'];
        foreach ($this->products as $product) {
            $headings[] = $product->name;
        }
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

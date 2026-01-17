<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Invoice;
use App\Models\Recovery;

class CreditDailyReportExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    public function collection()
    {
        $date = $this->date;

        // Credit invoices
        $creditInvoices = Invoice::where('is_credit', true)
            ->whereDate('invoice_date', $date)
            ->with(['customer', 'van'])
            ->get()
            ->map(fn($inv) => [
                'type' => 'Credit Sale',
                'number' => $inv->invoice_number,
                'customer' => ($inv->customer->customer_code ?? '-') . ' - ' . ($inv->customer->shop_name ?? '-'),
                'van' => $inv->van->code ?? '-',
                'amount' => (float) $inv->total_amount,
            ]);

        // Recoveries
        $recoveries = Recovery::whereDate('recovery_date', $date)
            ->with(['invoice.customer'])
            ->get()
            ->map(fn($rec) => [
                'type' => 'Recovery',
                'number' => $rec->invoice->invoice_number ?? '-',
                'customer' => ($rec->invoice->customer->customer_code ?? '-') . ' - ' . ($rec->invoice->customer->shop_name ?? '-'),
                'van' => '-',
                'amount' => (float) $rec->amount,
            ]);

        // Combine and add totals row if needed, or just list them.
        // Let's list them.
        
        $data = $creditInvoices->concat($recoveries);

        // Add summary at the bottom
        $totalCredit = $creditInvoices->sum('amount');
        $totalRecovery = $recoveries->sum('amount');
        
        $data->push([
            'type' => '', 'number' => '', 'customer' => '', 'van' => '', 'amount' => ''
        ]);
        $data->push([
            'type' => 'TOTAL CREDIT', 'number' => '', 'customer' => '', 'van' => '', 'amount' => $totalCredit
        ]);
        $data->push([
            'type' => 'TOTAL RECOVERY', 'number' => '', 'customer' => '', 'van' => '', 'amount' => $totalRecovery
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Type',
            'Reference #',
            'Customer',
            'Van',
            'Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Daily Credit Report ' . $this->date;
    }
}

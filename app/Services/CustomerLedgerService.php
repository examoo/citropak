<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Recovery;
use Illuminate\Support\Collection;

class CustomerLedgerService
{
    /**
     * Get ledger data for a customer.
     *
     * @param int $customerId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getLedgerData(int $customerId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return [
                'customer' => null,
                'entries' => [],
                'totals' => [
                    'debit' => 0,
                    'credit' => 0,
                    'balance' => 0,
                ],
            ];
        }

        $entries = collect();
        $openingBalance = (float) $customer->opening_balance;

        // Get invoices (credit sales - debit entries)
        $invoiceQuery = Invoice::where('customer_id', $customerId)
            ->where('is_credit', true);

        if ($dateFrom) {
            $invoiceQuery->whereDate('invoice_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $invoiceQuery->whereDate('invoice_date', '<=', $dateTo);
        }

        $invoices = $invoiceQuery->orderBy('invoice_date')->get();

        foreach ($invoices as $invoice) {
            $entries->push([
                'date' => $invoice->invoice_date->format('Y-m-d'),
                'reference' => $invoice->invoice_number,
                'description' => 'Invoice - Credit Sale',
                'debit' => (float) $invoice->total_amount,
                'credit' => 0,
                'type' => 'invoice',
            ]);
        }

        // Get recoveries (credit entries)
        $recoveryQuery = Recovery::whereHas('invoice', function ($q) use ($customerId) {
            $q->where('customer_id', $customerId);
        });

        if ($dateFrom) {
            $recoveryQuery->whereDate('recovery_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $recoveryQuery->whereDate('recovery_date', '<=', $dateTo);
        }

        $recoveries = $recoveryQuery->with('invoice')->orderBy('recovery_date')->get();

        foreach ($recoveries as $recovery) {
            $entries->push([
                'date' => $recovery->recovery_date->format('Y-m-d'),
                'reference' => 'RCV-' . str_pad($recovery->id, 6, '0', STR_PAD_LEFT),
                'description' => 'Recovery - Payment Received',
                'debit' => 0,
                'credit' => (float) $recovery->amount,
                'type' => 'recovery',
            ]);
        }

        // Sort all entries by date
        $entries = $entries->sortBy('date')->values();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        $entriesWithBalance = $entries->map(function ($entry) use (&$runningBalance, &$totalDebit, &$totalCredit) {
            $runningBalance = $runningBalance + $entry['debit'] - $entry['credit'];
            $totalDebit += $entry['debit'];
            $totalCredit += $entry['credit'];
            $entry['balance'] = $runningBalance;
            return $entry;
        });

        return [
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->customer_code,
                'name' => $customer->shop_name,
                'opening_balance' => $openingBalance,
            ],
            'entries' => $entriesWithBalance->values()->toArray(),
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'balance' => $runningBalance,
            ],
        ];
    }
}

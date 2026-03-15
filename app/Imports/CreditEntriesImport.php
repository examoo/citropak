<?php

namespace App\Imports;

use App\Models\Invoice;
use App\Models\Recovery;
use App\Models\Customer;
use App\Models\Van;
use App\Models\OrderBooker;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreditEntriesImport implements OnEachRow, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    protected int $distributionId;
    public int $importedCount = 0;
    public int $skippedCount  = 0;

    public function __construct(int $distributionId)
    {
        $this->distributionId = $distributionId;
    }

    /**
     * Process each row. Create an Invoice (represents credit entry) and optionally a Recovery.
     */
    public function onRow(Row $row)
    {
        $data = $row->toArray();

        $customerCode = $this->val($data, ['customercode', 'customer_code', 'code']);
        $balance      = $this->numeric($data, ['balance']);
        $recoveryAmt  = $this->numeric($data, ['recovery']);
        $date         = $this->parseDate($this->val($data, ['date']));
        $billNumber   = $this->val($data, ['bill', 'bill_number', 'billnumber']);

        // Skip rows with no date or no balance/recovery
        if (!$date || ($balance <= 0 && $recoveryAmt <= 0)) {
            $this->skippedCount++;
            return;
        }

        // Resolve customer
        $customer = null;
        if ($customerCode) {
            $customer = Customer::where('customer_code', $customerCode)
                ->where('distribution_id', $this->distributionId)
                ->first();
        }

        // Resolve Van and Order Booker if provided
        $vanRef = $this->val($data, ['van']);
        $vanId = null;
        if ($vanRef) {
            $van = Van::where('code', $vanRef)
                ->where('distribution_id', $this->distributionId)
                ->first();
            $vanId = $van?->id;
        }

        $obRef = $this->val($data, ['orderbookers', 'order_booker', 'orderbooker']);
        $obId = null;
        if ($obRef) {
            $ob = OrderBooker::where('name', $obRef)
                ->where('distribution_id', $this->distributionId)
                ->first();
            $obId = $ob?->id;
        }

        DB::transaction(function () use ($customer, $vanId, $obId, $balance, $recoveryAmt, $date, $billNumber) {
            // Check if an invoice with this number already exists for this distribution
            $invoice = null;
            if ($billNumber) {
                $invoice = Invoice::where('invoice_number', $billNumber)
                    ->where('distribution_id', $this->distributionId)
                    ->first();
            }

            if (!$invoice) {
                // Create Invoice (Credit Entry)
                $invoice = Invoice::create([
                    'distribution_id' => $this->distributionId,
                    'invoice_number'  => $billNumber ?? Invoice::generateInvoiceNumber($this->distributionId),
                    'van_id'          => $vanId,
                    'order_booker_id' => $obId,
                    'customer_id'     => $customer?->id,
                    'invoice_type'    => 'sale',
                    'is_credit'       => true,
                    'subtotal'        => $balance,
                    'total_amount'    => $balance,
                    'invoice_date'    => $date,
                    'created_by'      => Auth::id(),
                    'notes'           => 'Imported from Excel',
                ]);
                $this->importedCount++;
            } else {
                // If it exists, we might want to update it or just count it as existing.
                // For now, let's treat it as imported if it was potentially updated, 
                // but usually, we just attach recovery to it if recovery is > 0.
            }

            // Create Recovery if provided
            if ($recoveryAmt > 0) {
                Recovery::create([
                    'distribution_id' => $this->distributionId,
                    'invoice_id'      => $invoice->id,
                    'amount'          => $recoveryAmt,
                    'recovery_date'   => $date, // Use entry date as recovery date
                ]);
            }
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function val(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $slugKey = str_replace([' ', '#'], ['_', ''], strtolower($key));
            if (isset($row[$slugKey]) && trim((string) $row[$slugKey]) !== '') {
                return trim((string) $row[$slugKey]);
            }
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }
        return null;
    }

    private function numeric(array $row, array $keys): float
    {
        $raw = $this->val($row, $keys);
        if ($raw === null) return 0.0;
        // Remove commas and currency symbols if any
        return (float) preg_replace('/[^0-9.]/', '', $raw);
    }

    private function parseDate(?string $raw): ?string
    {
        if (!$raw) return null;

        if (is_numeric($raw)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'd.m.Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt && $dt->format($fmt) === $raw) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}

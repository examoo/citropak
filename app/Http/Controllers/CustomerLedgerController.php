<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerLedgerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerLedgerController extends Controller
{
    public function __construct(private CustomerLedgerService $ledgerService)
    {
    }

    public function index(Request $request): Response
    {
        $customerCode = $request->input('customer_code');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $ledgerData = [
            'customer' => null,
            'entries' => [],
            'totals' => [
                'debit' => 0,
                'credit' => 0,
                'balance' => 0,
            ],
        ];

        if ($customerCode) {
            $customer = Customer::where('customer_code', $customerCode)->first();
            
            if ($customer) {
                $ledgerData = $this->ledgerService->getLedgerData(
                    $customer->id,
                    $dateFrom,
                    $dateTo
                );
            }
        }

        return Inertia::render('CustomerLedger/Index', [
            'customer' => $ledgerData['customer'],
            'entries' => $ledgerData['entries'],
            'totals' => $ledgerData['totals'],
            'filters' => [
                'customer_code' => $customerCode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'customers' => Customer::select('id', 'customer_code', 'shop_name')
                ->orderBy('customer_code')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'code' => $c->customer_code,
                    'name' => $c->customer_code . ' - ' . $c->shop_name,
                ]),
        ]);
    }
}

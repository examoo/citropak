<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerAttribute;
use App\Models\Van;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToModel, WithHeadingRow
{
    protected $distributionId;

    public function __construct($distributionId = null)
    {
        $this->distributionId = $distributionId;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $shopName = $this->getValue($row, 'shopname') ?? $this->getValue($row, 'name');
        
        // Skip if shop_name is missing
        if (!$shopName) {
            return null;
        }

        // Determine Distribution ID
        // If provided in constructor (scoped user), use it.
        // If not (global user), try to find from file.
        $distributionId = $this->distributionId;
        if (!$distributionId) {
            $distName = $this->getValue($row, 'distribution');
            if ($distName) {
                // Find distribution by name or code
                $dist = \App\Models\Distribution::where('name', $distName)
                    ->orWhere('code', $distName)
                    ->first();
                if ($dist) {
                    $distributionId = $dist->id;
                }
            }
        }

        // Auto-create Van if missing (scoped)
        $vanName = $this->getValue($row, 'van');
        if ($vanName) {
            Van::firstOrCreate(
                ['code' => $vanName, 'distribution_id' => $distributionId],
                ['status' => 'active']
            );
        }

        // Auto-create Channel if missing (scoped)
        $channelName = $this->getValue($row, 'channel');
        if ($channelName) {
            $atl = $this->getValue($row, 'atl') == 'active';
            $advTax = $this->getValue($row, 'adv_tax') ?? 0;
            
            \App\Models\Channel::firstOrCreate(
                ['name' => $channelName, 'distribution_id' => $distributionId],
                ['status' => 'active', 'atl' => $atl, 'adv_tax_percent' => floatval($advTax)]
            );
        }

        // Auto-create Category if missing (scoped)
        $catName = $this->getValue($row, 'categories') ?? $this->getValue($row, 'category');
        if ($catName) {
            \App\Models\Category::firstOrCreate(
                ['name' => $catName, 'distribution_id' => $distributionId],
                ['status' => 'active']
            );
        }

        // Auto-create SubAddress if missing (scoped)
        $subAddress = $this->getValue($row, 'subaddress') ?? $this->getValue($row, 'sub_address');
        if ($subAddress) {
             \App\Models\SubAddress::firstOrCreate(
                 ['name' => $subAddress, 'distribution_id' => $distributionId],
                 ['status' => 'active']
             );
        }

        // Auto-create SubDistribution if missing (scoped)
        $subDistName = $this->getValue($row, 'subdistribution') ?? $this->getValue($row, 'sub_distribution');
        if ($subDistName) {
             \App\Models\SubDistribution::firstOrCreate(
                 ['name' => $subDistName, 'distribution_id' => $distributionId],
                 ['status' => 'active']
             );
        }

        // Auto-create Route if missing (scoped)
        $routeName = $this->getValue($row, 'route');
        if ($routeName) {
             \App\Models\Route::firstOrCreate(
                 ['name' => $routeName, 'distribution_id' => $distributionId],
                 ['status' => 'active']
             );
        }

        // Determine customer status (default to active)
        $status = $this->getValue($row, 'status');
        if ($status) {
            $status = strtolower(trim($status)) === 'inactive' ? 'inactive' : 'active';
        } else {
            $status = 'active';
        }

        // Normalize sales_tax_status (default to active)
        $salesTaxStatus = $this->getValue($row, 'st_status') ?? $this->getValue($row, 'saletaxstatus') ?? $this->getValue($row, 'sales_tax_status');
        if ($salesTaxStatus) {
            $salesTaxStatus = strtolower(trim($salesTaxStatus)) === 'inactive' ? 'inactive' : 'active';
        } else {
            $salesTaxStatus = 'active';
        }

        // Normalize ATL status (default to active)
        $atlStatus = $this->getValue($row, 'atl_status') ?? $this->getValue($row, 'atl');
        if ($atlStatus) {
            $atlStatus = strtolower(trim($atlStatus)) === 'inactive' ? 'inactive' : 'active';
        } else {
            $atlStatus = 'active';
        }

        return new Customer([
            'shop_name'         => $shopName,
            'customer_code'     => $this->getValue($row, 'customercode') ?? $this->getValue($row, 'code'),
            'van'               => $vanName,
            'channel'           => $channelName,
            'category'          => $catName,
            'address'           => $this->getValue($row, 'address'),
            'sub_address'       => $subAddress,
            'route'             => $routeName,
            'sub_distribution'  => $subDistName,
            'phone'             => $this->getValue($row, 'telephone') ?? $this->getValue($row, 'phone'),
            'ntn_number'        => $this->getValue($row, 'ntnnumber') ?? $this->getValue($row, 'ntn'),
            'sales_tax_number'  => $this->getValue($row, 'salestaxnumber') ?? $this->getValue($row, 'sales_tax_number') ?? $this->getValue($row, 'sales_tax'),
            'cnic'              => $this->getValue($row, 'cnic'),
            'sales_tax_status'  => $salesTaxStatus,
            'distribution_id'   => $distributionId,
            'day'               => $this->getValue($row, 'day'),
            'status'            => $status,
            'atl'               => $atlStatus,
            'adv_tax_percent'   => floatval($this->getValue($row, 'adv_tax') ?? 0),
            'percentage'        => floatval($this->getValue($row, 'percentage') ?? $this->getValue($row, 'pecentage') ?? 0),
            'opening_balance'   => floatval($this->getValue($row, 'openingbalance') ?? $this->getValue($row, 'opening_balance') ?? 0),
        ]);
    }

    private function getValue($row, $key)
    {
        return isset($row[$key]) ? trim($row[$key]) : null;
    }
}

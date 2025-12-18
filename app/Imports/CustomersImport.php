<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerAttribute;
use App\Models\Van;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class CustomersImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $shopName = $this->getValue($row, 'shopname');
        
        // Skip if shop_name is missing
        if (!$shopName) {
            return null;
        }

        // Auto-create Van if missing
        $vanName = $this->getValue($row, 'van');
        if ($vanName) {
            Van::firstOrCreate(['name' => $vanName], ['status' => 'active']);
        }

        // Auto-create Channel if missing
        $channelName = $this->getValue($row, 'channel');
        if ($channelName) {
            $atl = $this->getValue($row, 'atl') ?? 'active';
            $advTax = $this->getValue($row, 'adv_tax') ?? 0;
            
            // Note: We search by value (name) only. If it exists, we don't update ATL/Tax
            // to preserve existing settings. Only create new ones with provided ATL/Tax.
            CustomerAttribute::firstOrCreate(
                ['type' => 'channel', 'value' => $channelName],
                ['atl' => $atl, 'adv_tax_percent' => floatval($advTax)]
            );
        }

        // Auto-create Distribution if missing
        $distName = $this->getValue($row, 'distribution');
        if ($distName) {
            CustomerAttribute::firstOrCreate(['type' => 'distribution', 'value' => $distName]);
        }

        // Auto-create Category if missing
        $catName = $this->getValue($row, 'categories');
        if ($catName) {
            CustomerAttribute::firstOrCreate(['type' => 'category', 'value' => $catName]);
        }

        // Auto-create SubAddress if missing
        $subAddress = $this->getValue($row, 'subaddress');
        if ($subAddress) {
             CustomerAttribute::firstOrCreate(['type' => 'sub_address', 'value' => $subAddress]);
        }

        return new Customer([
            'customer_code'     => $this->getValue($row, 'customercode'),
            'van'               => $vanName,
            'shop_name'         => $shopName,
            'address'           => $this->getValue($row, 'address'),
            'sub_address'       => $subAddress,
            'phone'             => $this->getValue($row, 'telephone'),
            'category'          => $catName,
            'channel'           => $channelName,
            'ntn_number'        => $this->getValue($row, 'ntnnumber'),
            'cnic'              => $this->getValue($row, 'cnic'),
            'sales_tax_number'  => $this->getValue($row, 'saletaxstatus'), // Assuming this maps to sales_tax
            'distribution'      => $distName,
            'day'               => $this->getValue($row, 'day'),
            'status'            => 'active',
            'atl'               => $this->getValue($row, 'atl') ?? 'active',
            'adv_tax_percent'   => floatval($this->getValue($row, 'adv_tax') ?? 0),
            'percentage'        => floatval($this->getValue($row, 'percentage') ?? 0),
        ]);
    }

    private function getValue($row, $key)
    {
        return isset($row[$key]) ? trim($row[$key]) : null;
    }
}

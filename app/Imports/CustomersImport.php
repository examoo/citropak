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

        // Get values from Excel
        $vanName = $this->getValue($row, 'van');
        $channelName = $this->getValue($row, 'channel');
        $catName = $this->getValue($row, 'categories') ?? $this->getValue($row, 'category');
        $subAddress = $this->getValue($row, 'subaddress') ?? $this->getValue($row, 'sub_address');
        $subDistName = $this->getValue($row, 'subdistribution') ?? $this->getValue($row, 'sub_distribution');
        $routeName = $this->getValue($row, 'route');

        // Normalize ATL status from Excel (default to active)
        // ATL in database is boolean: true (1) = ATL active, false (0/null) = not ATL
        $atlStatusRaw = $this->getValue($row, 'atl_status') ?? $this->getValue($row, 'atl');
        $atlBool = true; // Default to ATL active (true)
        if ($atlStatusRaw) {
            $atlLower = strtolower(trim($atlStatusRaw));
            // Explicitly check: 'active', 'yes', '1', 'true', 'y' = true (ATL)
            // 'inactive', 'no', '0', 'false', 'n' = false (not ATL)
            if (in_array($atlLower, ['active', 'yes', '1', 'true', 'y'])) {
                $atlBool = true;
            } elseif (in_array($atlLower, ['inactive', 'no', '0', 'false', 'n'])) {
                $atlBool = false;
            }
            // If not recognized, keep default (true)
        }
        // For customer's atl field (stored as 'active'/'inactive' string)
        $atlStatus = $atlBool ? 'active' : 'inactive';

        // Get adv_tax from Excel
        $advTaxRaw = $this->getValue($row, 'adv_tax') ?? '0';
        $advTaxFromExcel = 0;
        
        if ($advTaxRaw !== null && $advTaxRaw !== '') {
            // Check if value contains % symbol (formatted as percentage text)
            if (strpos($advTaxRaw, '%') !== false) {
                // Remove % symbol and whitespace, then convert to float
                $advTaxFromExcel = floatval(str_replace(['%', ' '], '', $advTaxRaw));
            } else {
                // Excel stores 0.50% as 0.005 internally (decimal format)
                // If value is less than 1, it's likely a decimal that needs to be multiplied by 100
                $numericValue = floatval($advTaxRaw);
                if ($numericValue > 0 && $numericValue < 1) {
                    $advTaxFromExcel = $numericValue * 100;
                } else {
                    $advTaxFromExcel = $numericValue;
                }
            }
        }
        
        // Find or create channel, get its ID
        $channelId = null;
        $advTaxPercent = $advTaxFromExcel;
        
        if ($channelName) {
            // Build query to find channel by name
            $channelQuery = \App\Models\Channel::where('name', $channelName);
            
            // Match ATL status: true (1) for active, false/null/0 for inactive
            if ($atlBool) {
                // Find channel where ATL = true (1)
                $channelQuery->where('atl', 1);
            } else {
                // Find channel where ATL = false (0 or null)
                $channelQuery->where(function ($q) {
                    $q->where('atl', 0)
                      ->orWhereNull('atl');
                });
            }
            
            // Scope to distribution if provided, also check global channels
            if ($distributionId) {
                $channelQuery->where(function ($q) use ($distributionId) {
                    $q->where('distribution_id', $distributionId)
                      ->orWhereNull('distribution_id');
                });
            }
            
            $existingChannel = $channelQuery->first();
            
            if ($existingChannel) {
                // Use existing channel's ID
                $channelId = $existingChannel->id;
                $advTaxPercent = $advTaxFromExcel;
            } elseif ($distributionId) {
                // Create new channel if distribution is provided
                $newChannel = \App\Models\Channel::create([
                    'name' => $channelName,
                    'distribution_id' => $distributionId,
                    'atl' => $atlBool,
                    'status' => 'active',
                    'adv_tax_percent' => $advTaxFromExcel
                ]);
                $channelId = $newChannel->id;
            }
        }

        // Auto-create related entities only when distribution is provided
        // (These tables require distribution_id to be NOT NULL)
        if ($distributionId) {
            // Auto-create Van if missing (scoped)
            if ($vanName) {
                Van::firstOrCreate(
                    ['code' => $vanName, 'distribution_id' => $distributionId],
                    ['status' => 'active']
                );
            }

            // Auto-create Category if missing (scoped)
            if ($catName) {
                \App\Models\Category::firstOrCreate(
                    ['name' => $catName, 'distribution_id' => $distributionId],
                    ['status' => 'active']
                );
            }

            // Auto-create SubAddress if missing (scoped)
            if ($subAddress) {
                 \App\Models\SubAddress::firstOrCreate(
                     ['name' => $subAddress, 'distribution_id' => $distributionId],
                     ['status' => 'active']
                 );
            }

            // Auto-create SubDistribution if missing (scoped)
            if ($subDistName) {
                 \App\Models\SubDistribution::firstOrCreate(
                     ['name' => $subDistName, 'distribution_id' => $distributionId],
                     ['status' => 'active']
                 );
            }

            // Auto-create Route if missing (scoped)
            if ($routeName) {
                 \App\Models\Route::firstOrCreate(
                     ['name' => $routeName, 'distribution_id' => $distributionId],
                     ['status' => 'active']
                 );
            }
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

        return new Customer([
            'shop_name'         => $shopName,
            'customer_code'     => $this->getValue($row, 'customercode') ?? $this->getValue($row, 'code'),
            'van'               => $vanName,
            'channel'           => $channelName,
            'channel_id'        => $channelId,
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
            'adv_tax_percent'   => $advTaxPercent,
            'percentage'        => floatval($this->getValue($row, 'percentage') ?? $this->getValue($row, 'pecentage') ?? 0),
            'opening_balance'   => floatval($this->getValue($row, 'openingbalance') ?? $this->getValue($row, 'opening_balance') ?? 0),
        ]);
    }

    private function getValue($row, $key)
    {
        return isset($row[$key]) ? trim($row[$key]) : null;
    }
}

<?php

namespace App\Imports;

use App\Models\DiscountScheme;
use App\Models\Distribution;
use App\Models\SubDistribution;
use App\Models\Product;
use App\Models\Brand;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DiscountSchemesImport implements ToModel, WithHeadingRow
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
        $name = $this->getValue($row, 'name');
        
        // Skip if name is missing
        if (!$name) {
            return null;
        }

        // Determine Distribution ID
        $distributionId = $this->distributionId;
        if (!$distributionId) {
            $distName = $this->getValue($row, 'distribution');
            if ($distName) {
                $dist = Distribution::where('name', $distName)
                    ->orWhere('code', $distName)
                    ->first();
                if ($dist) {
                    $distributionId = $dist->id;
                }
            }
        }

        // Find Sub Distribution
        $subDistributionId = null;
        $subDistName = $this->getValue($row, 'subdistribution') ?? $this->getValue($row, 'sub_distribution');
        if ($subDistName) {
            $subDist = SubDistribution::where('name', $subDistName)
                ->when($distributionId, fn($q) => $q->where('distribution_id', $distributionId))
                ->first();
            if ($subDist) {
                $subDistributionId = $subDist->id;
            }
        }

        // Scheme type
        $schemeType = strtolower($this->getValue($row, 'scheme_type') ?? $this->getValue($row, 'schemetype') ?? 'product');
        if (!in_array($schemeType, ['product', 'brand'])) {
            $schemeType = 'product';
        }

        // Find Product
        $productId = null;
        $productCode = $this->getValue($row, 'product_code') ?? $this->getValue($row, 'productcode') ?? $this->getValue($row, 'dms_code');
        if ($productCode && $schemeType === 'product') {
            $product = Product::where('dms_code', $productCode)
                ->orWhere('name', $productCode)
                ->first();
            if ($product) {
                $productId = $product->id;
            }
        }

        // Find Brand
        $brandId = null;
        $brandName = $this->getValue($row, 'brand_name') ?? $this->getValue($row, 'brandname') ?? $this->getValue($row, 'brand');
        if ($brandName && $schemeType === 'brand') {
            $brand = Brand::where('name', $brandName)->first();
            if ($brand) {
                $brandId = $brand->id;
            }
        }

        // Parse dates
        $startDate = $this->parseDate($this->getValue($row, 'start_date') ?? $this->getValue($row, 'startdate'));
        $endDate = $this->parseDate($this->getValue($row, 'end_date') ?? $this->getValue($row, 'enddate'));

        return new DiscountScheme([
            'name' => $name,
            'distribution_id' => $distributionId,
            'sub_distribution_id' => $subDistributionId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'scheme_type' => $schemeType,
            'product_id' => $productId,
            'brand_id' => $brandId,
            'from_qty' => intval($this->getValue($row, 'from_qty') ?? $this->getValue($row, 'fromqty') ?? 1),
            'to_qty' => intval($this->getValue($row, 'to_qty') ?? $this->getValue($row, 'toqty') ?? 0) ?: null,
            'pieces' => intval($this->getValue($row, 'pieces') ?? 0) ?: null,
            'free_product_code' => $this->getValue($row, 'free_product_code') ?? $this->getValue($row, 'freeproductcode'),
            'amount_less' => floatval($this->getValue($row, 'amount_less') ?? $this->getValue($row, 'amountless') ?? 0),
            'status' => strtolower($this->getValue($row, 'status') ?? 'active') === 'active' ? 'active' : 'inactive',
        ]);
    }

    private function getValue($row, $key)
    {
        // Try with underscores
        if (isset($row[$key]) && trim($row[$key]) !== '') {
            return trim($row[$key]);
        }
        // Try without underscores
        $keyNoUnderscore = str_replace('_', '', $key);
        if (isset($row[$keyNoUnderscore]) && trim($row[$keyNoUnderscore]) !== '') {
            return trim($row[$keyNoUnderscore]);
        }
        return null;
    }

    private function parseDate($value)
    {
        if (!$value) return null;
        
        // Handle Excel serial date
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        
        // Handle string date
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}

<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductType;
use App\Models\Packing;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Str;

class ProductsImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function headingRow(): int
    {
        return 3;
    }

    public function model(array $row)
    {
        // Debug Log
        \Illuminate\Support\Facades\Log::info('Importing Row:', $row);

        // Robust Key Mapping
        // With headingRow=3, keys should be correct (product_name, brand, etc.)
        // But keeping fallbacks just in case
        
        $name = $this->getValue($row, ['product_name', 'name', 0]);
        
        if (!$name) {
             \Illuminate\Support\Facades\Log::warning('Skipping row due to missing name', $row);
             return null;
        }

        $dmsCode = $this->getValue($row, ['product_code', 'code', 'product_format']);

        try {

            // Auto-create Brand
            $brandName = $this->getValue($row, ['brand', 1]);
            $brandId = null;
            if ($brandName) {
                $brand = Brand::firstOrCreate(
                    ['name' => $brandName],
                    ['status' => 'active']
                );
                $brandId = $brand->id;
            }

            // Auto-create Product Type
            $typeName = $this->getValue($row, ['product_type', 'producttype', 11]);
            $typeId = null;
            if ($typeName) {
                $type = ProductType::firstOrCreate(
                    ['name' => $typeName]
                );
                $typeId = $type->id;
            }

            // Packing Logic: "if packing is not in skip this"
            // We check for a specific 'packing' column. If not found, we skip packing assignment.
            // We NO LONGER use 'sku' column for packing.
            $packingName = $this->getValue($row, ['packing', 'packing_name']);
            $packingId = null;
            $piecesPerPacking = 1;

            if ($packingName) {
                // If explicit packing column exists, logic to find/create it can go here.
                // For now, assuming user file doesn't have it based on logs, so this block might not run.
                // But if they add it later:
                $packing = Packing::firstOrCreate(
                    ['name' => $packingName],
                    ['status' => 'active', 'conversion' => 1] // Default conversion if unknown
                );
                $packingId = $packing->id;
            }

            // SKU Logic: "sku and code are to seprate fields"
            // map Sku column to sku field
            $sku = $this->getValue($row, ['sku', 2]);

            // Clean Percentages
            $retailMargin = $this->cleanPercentage($this->getValue($row, ['retail_margin', 'retailmargin', 7]));
            $distMargin = $this->cleanPercentage($this->getValue($row, ['distribution_margin', 'distributionmargin', 8]));
            $saleTax = $this->cleanPercentage($this->getValue($row, ['sale_tax', 'saletax', 9]));
            $fed = $this->cleanPercentage($this->getValue($row, ['fed', 10]));
            
            // Prices
            $exclusiveValue = $this->cleanNumber($this->getValue($row, ['exclusive_value', 'exclusivevalue', 3]));
            $unitPrice = $this->cleanNumber($this->getValue($row, ['unit_price', 'unitprice', 6]));
            $tpRate = $this->cleanNumber($this->getValue($row, ['t_p_rate', 'tp_rate', 4])); 
            $invoicePrice = $this->cleanNumber($this->getValue($row, ['invoice_price', 'invoiceprice', 5]));
            $reorderLevel = $this->cleanNumber($this->getValue($row, ['re_order_level', 'reorder_level', 12])) ?? 0;

            // Find existing product by DMS Code
            $product = Product::updateOrCreate(
                ['dms_code' => $dmsCode], 
                [
                    'name' => $name,
                    'brand_id' => $brandId,
                    'type_id' => $typeId,
                    'packing_id' => $packingId,
                    'pieces_per_packing' => $piecesPerPacking,
                    'sku' => $sku, 
                    'list_price_before_tax' => $exclusiveValue,
                    'retail_margin' => $retailMargin,
                    'distribution_margin' => $distMargin,
                    'fed_sales_tax' => $saleTax,
                    'fed_percent' => $fed,
                    'unit_price' => $unitPrice,
                    'tp_rate' => $tpRate,
                    'invoice_price' => $invoicePrice,
                    'reorder_level' => $reorderLevel,
                ]
            );

            return $product;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Product Import Error: ' . $e->getMessage(), ['row' => $row]);
            return null;
        }
    }

    private function getValue($row, $keys)
    {
        if (!is_array($keys)) $keys = [$keys];
        
        foreach ($keys as $key) {
            // Try as is
            if (isset($row[$key])) return trim($row[$key]);
            
            // Try as string (for numeric keys compatibility if needed, though row keys are strictly typed)
            
            // If key is string, try cleanup variations
            if (is_string($key)) {
                $slug = Str::slug($key, '_');
                if (isset($row[$slug])) return trim($row[$slug]);
                
                $clean = str_replace('_', '', $key);
                if (isset($row[$clean])) return trim($row[$clean]);
            }
        }
        
        return null;
    }

    private function cleanPercentage($value)
    {
        if (is_null($value)) return 0;
        
        // Handle string with % (e.g. "20%")
        if (is_string($value) && str_contains($value, '%')) {
            $value = str_replace('%', '', $value);
            return floatval($value);
        }
        
        $floatVal = floatval($value);
        
        // Handle Excel decimal format (e.g. 0.18 -> 18, 0.2 -> 20)
        // If value is <= 1 (and not 0), assume it's a ratio and convert to percent
        // This handles cases where Excel returns 0.18 for 18%
        if ($floatVal <= 1 && $floatVal != 0) {
            return $floatVal * 100;
        }
        
        return $floatVal;
    }

    private function cleanNumber($value)
    {
        if (is_null($value)) return 0;
        // Remove commas if any
        $value = str_replace(',', '', $value);
        return floatval($value);
    }
}

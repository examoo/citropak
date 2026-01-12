<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductType;
use App\Models\Packing;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductsImport implements ToModel, WithCalculatedFormulas, SkipsEmptyRows
{
    private $importedCount = 0;
    private $skippedCount = 0;
    
    // Dynamic Header Detection
    private $headerRowIndex = null;
    private $headerMap = []; // Maps field name => numeric index
    private $maxScanRows = 10; // How many rows to scan for header
    private $currentRowIndex = 0;

    /**
     * Process each row
     */
    public function model(array $row)
    {
        $this->currentRowIndex++;
        
        // 1. Detect Header if not found yet
        if ($this->headerRowIndex === null) {
            if ($this->isHeaderRow($row)) {
                $this->headerRowIndex = $this->currentRowIndex;
                $this->headerMap = $this->mapHeaders($row);
                Log::info('ProductsImport - Found Header at Row: ' . $this->currentRowIndex, $this->headerMap);
                return null; // Skip the header row itself
            }
            
            // If we've scanned too many rows and still no header
            if ($this->currentRowIndex >= $this->maxScanRows) {
                Log::warning('ProductsImport - Could not find header in first ' . $this->maxScanRows . ' rows.');
                // Fallback to default mapping (0=name, etc) if really needed, or just fail
            }
            
            return null; // Skip pre-header rows
        }

        // 2. Process Data Rows
        return $this->processDataRow($row);
    }
    
    /**
     * Check if this row looks like a header row
     */
    private function isHeaderRow(array $row)
    {
        // Convert all values to lowercase strings for checking
        $values = array_map(function($val) {
            return strtolower(trim((string)$val));
        }, $row);
        
        // Critical columns that MUST be present to be considered a header
        $required = ['product_name', 'product name', 'productname', 'name'];
        $found = false;
        
        foreach ($values as $val) {
            if (in_array($val, $required)) {
                $found = true;
                break;
            }
        }
        
        // Optional: Check for other common columns to increase confidence
        if ($found) {
             Log::info('ProductsImport - Potential Header Row Found:', $row);
        }
        
        return $found;
    }
    
    /**
     * Map header names to column indices
     */
    private function mapHeaders(array $row)
    {
        $map = [];
        foreach ($row as $index => $value) {
            if (empty($value)) continue;
            
            // Normalize key: lowercase, trim, snake_case
            $key = Str::slug($value, '_'); 
            $map[$key] = $index;
            
            // Also store exact value for robust matching
            $map[strtolower(trim($value))] = $index;
            
            // Store cleaned version without underscores
            $map[str_replace('_', '', $key)] = $index;
        }
        return $map;
    }

    /**
     * Get value using the dynamic header map
     */
    private function getMappedValue(array $row, array $keys)
    {
        // 1. Try finding index from Header Map
        foreach ($keys as $key) {
            if (isset($this->headerMap[$key])) {
                $index = $this->headerMap[$key];
                if (isset($row[$index])) return trim((string)$row[$index]);
            }
            
            // Try variations
            $slugKey = Str::slug($key, '_');
            if (isset($this->headerMap[$slugKey])) {
                $index = $this->headerMap[$slugKey];
                if (isset($row[$index])) return trim((string)$row[$index]);
            }
        }
        
        // 2. Fallback to Numeric Indices (Critical for robust import)
        // This handles cases where header detection worked but specific column name doesn't match
        foreach ($keys as $key) {
             if (is_int($key) && isset($row[$key])) {
                 return trim((string)$row[$key]);
             }
        }
        
        return null;
    }

    private function processDataRow(array $row)
    {
        // Debug: Log the raw row
        // Log::info('ProductsImport - Processing Row:', $row);

        // Get product name
        $name = $this->getMappedValue($row, ['product_name', 'name', 'productname', 0]);
        
        if (empty($name)) {
            Log::warning('ProductsImport - Skipping row: No product name found', $row);
            $this->skippedCount++;
            return null;
        }
        
        // Check if this accidentally looks like a header row (repeated header)
        if ($this->isHeaderRow($row)) {
             return null;
        }

        // Get DMS/Product Code
        $dmsCode = $this->getMappedValue($row, ['product_code', 'code', 'dms_code', 'product_format']);

        // If no code, generate one from name
        if (empty($dmsCode)) {
            $dmsCode = strtoupper(Str::slug($name, '-')) . '-' . time();
        }

        try {
            // Auto-create Brand
            $brandName = $this->getMappedValue($row, ['brand', 'brand_name', 1]);
            $brandId = null;
            if (!empty($brandName)) {
                $brand = \App\Models\Brand::firstOrCreate(
                    ['name' => trim($brandName)],
                    ['status' => 'active']
                );
                $brandId = $brand->id;
            }

            // Auto-create Product Type
            $typeName = $this->getMappedValue($row, ['product_type', 'type', 'producttype', 11]);
            $typeId = null;
            if (!empty($typeName)) {
                $type = \App\Models\ProductType::firstOrCreate(
                    ['name' => trim($typeName)]
                );
                $typeId = $type->id;
            }

            // Packing
            $packingName = $this->getMappedValue($row, ['packing', 'packing_name', 13]);
            $packingId = null;
            if (!empty($packingName)) {
                $packing = \App\Models\Packing::firstOrCreate(
                    ['name' => trim($packingName)],
                    ['status' => 'active', 'conversion' => 1]
                );
                $packingId = $packing->id;
            }

            // SKU
            $sku = $this->getMappedValue($row, ['sku', 'sku_code', 2]);

            // Prices
            $exclusiveValue = $this->cleanNumber($this->getMappedValue($row, ['exclusive_value', 'list_price_before_tax', 3]));
            $tpRate = $this->cleanNumber($this->getMappedValue($row, ['tp_rate', 't_p_rate', 4]));
            $invoicePrice = $this->cleanNumber($this->getMappedValue($row, ['invoice_price', 5]));
            $unitPrice = $this->cleanNumber($this->getMappedValue($row, ['unit_price', 6]));
            
            // Margins
            $retailMargin = $this->cleanPercentage($this->getMappedValue($row, ['retail_margin', 7]));
            $distMargin = $this->cleanPercentage($this->getMappedValue($row, ['distribution_margin', 'dist_margin', 8]));
            $saleTax = $this->cleanPercentage($this->getMappedValue($row, ['sale_tax', 'sales_tax', 9]));
            $fed = $this->cleanPercentage($this->getMappedValue($row, ['fed', 'fed_percent', 10]));
            
            $reorderLevel = (int) $this->cleanNumber($this->getMappedValue($row, ['reorder_level', 're_order_level', 12]));

            // Create or update product
            $product = Product::updateOrCreate(
                ['dms_code' => $dmsCode], 
                [
                    'name' => $name,
                    'brand_id' => $brandId,
                    'type_id' => $typeId,
                    'packing_id' => $packingId,
                    'pieces_per_packing' => 1,
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
                    'status' => 'active',
                ]
            );

            $this->importedCount++;
            return $product;

        } catch (\Exception $e) {
            Log::error('ProductsImport - Error: ' . $e->getMessage(), ['row' => $row]);
            $this->skippedCount++;
            return null;
        }
    }


    /**
     * Clean percentage value from Excel
     */
    private function cleanPercentage($value): float
    {
        if ($value === null || $value === '') return 0;
        
        // Handle string with % symbol
        if (is_string($value) && str_contains($value, '%')) {
            $value = str_replace('%', '', $value);
            return floatval(trim($value));
        }
        
        $floatVal = floatval($value);
        
        // If value is between 0 and 1, assume it's a decimal ratio (0.18 = 18%)
        if ($floatVal > 0 && $floatVal <= 1) {
            return $floatVal * 100;
        }
        
        return $floatVal;
    }

    /**
     * Clean number value from Excel
     */
    private function cleanNumber($value): float
    {
        if ($value === null || $value === '') return 0;
        
        // Remove commas and other formatting
        $value = str_replace([',', ' '], '', (string) $value);
        
        return floatval($value);
    }

    /**
     * Get import statistics
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}

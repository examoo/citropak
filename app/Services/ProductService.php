<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get all products with filtering and pagination
     */
    public function getAll($filters = [])
    {
        $query = Product::query()->with(['brand', 'productType', 'packing']);

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dms_code', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by Type
        if (!empty($filters['type'])) {
            $query->whereHas('productType', function($q) use ($filters) {
                $q->where('name', $filters['type']);
            });
        }

        // Sort
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        // Whitelist sort fields to prevent SQL injection or errors
        $allowedSorts = ['name', 'dms_code', 'list_price_before_tax', 'unit_price', 'created_at', 'tp_rate'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->latest();
        }

        return $query->paginate(10)->withQueryString();
    }

    /**
     * Create a new product
     */
    public function create(array $data): Product
    {
        return Product::create($this->roundNumericFields($data));
    }

    /**
     * Find product by ID
     */
    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * Update product
     */
    public function update(int $id, array $data): bool
    {
        $product = Product::findOrFail($id);
        return $product->update($this->roundNumericFields($data));
    }

    /**
     * Delete product and all related records
     */
    public function delete(int $id): bool
    {
        $product = Product::findOrFail($id);
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($product, $id) {
            // Delete related records in order (children first)
            \App\Models\GoodIssueNoteItem::where('product_id', $id)->delete();
            \App\Models\InvoiceItem::where('product_id', $id)->delete();
            \App\Models\StockInItem::where('product_id', $id)->delete();
            \App\Models\StockOutItem::where('product_id', $id)->delete();
            \App\Models\Stock::where('product_id', $id)->delete();
            \App\Models\StockLedger::where('product_id', $id)->delete();
            \App\Models\OpeningStock::where('product_id', $id)->delete();
            \App\Models\ClosingStock::where('product_id', $id)->delete();
            
            // Delete product variants
            $product->variants()->delete();
            
            // Finally delete the product
            return $product->delete();
        });
    }

    /**
     * Round numeric fields to 4 decimal places
     */
    private function roundNumericFields(array $data): array
    {
        $numericFields = [
            'list_price_before_tax',
            'retail_margin',
            'tp_rate',
            'distribution_margin',
            'invoice_price',
            'fed_sales_tax',
            'fed_percent',
            'unit_price',
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $data[$field] = round((float) $data[$field], 4);
            }
        }

        return $data;
    }
}

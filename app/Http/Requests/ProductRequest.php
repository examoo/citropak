<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = null;
        if ($product = $this->route('product')) {
            $id = $product instanceof \App\Models\Product ? $product->id : $product;
        }

        return [
            'dms_code' => 'nullable|string|max:255',               // Code
            'name' => 'required|string|max:255',                   // Product Name
            'brand_id' => 'required|exists:brands,id',             // Brand
            'type_id' => 'required|exists:product_types,id',       // Types
            'packing_id' => 'nullable|exists:packings,id',         // Packing
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $id, // SKU
            'list_price_before_tax' => 'required|numeric|min:0',   // Exclusive Value
            'retail_margin' => 'nullable|numeric|min:0',           // Retail Margin %
            'tp_rate' => 'nullable|numeric|min:0',                 // T.P Rate
            'distribution_margin' => 'nullable|numeric|min:0',     // Distribution Margin %
            'invoice_price' => 'nullable|numeric|min:0',           // Invoice Price
            'fed_sales_tax' => 'nullable|numeric|min:0',           // Sale Tax %
            'fed_percent' => 'nullable|numeric|min:0',             // Fed %
            'unit_price' => 'required|numeric|min:0',              // Unit Price (Final)
            'reorder_level' => 'nullable|integer|min:0',           // Reorder Level
            'pieces_per_packing' => 'nullable|integer|min:1',      // Per Packing Piece
        ];
    }
}

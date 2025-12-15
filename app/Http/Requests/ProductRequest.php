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
            'name' => 'required|string|max:255',
            'dms_code' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'list_price_before_tax' => 'required|numeric|min:0',
            'fed_tax_percent' => 'required|numeric|min:0',
            'fed_sales_tax' => 'required|numeric|min:0',
            'net_list_price' => 'required|numeric|min:0',
            'distribution_margin' => 'nullable|numeric|min:0',
            'distribution_manager_percent' => 'nullable|numeric|min:0',
            'trade_price_before_tax' => 'required|numeric|min:0',
            'fed_2' => 'nullable|numeric|min:0',
            'sales_tax_3' => 'nullable|numeric|min:0',
            'net_trade_price' => 'required|numeric|min:0',
            'retailer_margin' => 'nullable|numeric|min:0',
            'retailer_margin_4' => 'nullable|numeric|min:0',
            'consumer_price_before_tax' => 'required|numeric|min:0',
            'fed_5' => 'nullable|numeric|min:0',
            'sales_tax_6' => 'nullable|numeric|min:0',
            'net_consumer_price' => 'required|numeric|min:0',
            'total_margin' => 'nullable|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'packing' => 'nullable|string|max:255',
            'packing_one' => 'nullable|string|max:255',
            'reorder_level' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ];
    }
}

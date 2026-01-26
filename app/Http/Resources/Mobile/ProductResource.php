<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->dms_code,
            'sku' => $this->sku,
            'packing_size' => $this->pieces_per_packing ?? 1,
            'brand_id' => $this->brand_id,
            // Product Type for Food/Non-Food calculation
            'product_type_id' => $this->product_type_id,
            'product_type' => $this->productType ? [
                'id' => $this->productType->id,
                'name' => $this->productType->name,
                'extra_tax' => $this->productType->extra_tax ?? 0,
            ] : null,
            // Pricing
            'list_price' => $this->list_price_before_tax, // Base Price (Exclusive)
            'retail_margin' => $this->retail_margin,
            'tp_rate' => $this->tp_rate,
            'dist_margin' => $this->distribution_margin,
            'invoice_price' => $this->invoice_price,
            'unit_price' => $this->unit_price, // Final Unit Price (Net)
            // Taxes
            'gst_percent' => $this->fed_sales_tax,
            'fed_percent' => $this->fed_percent,
            // Stock
            'stock_qty' => $this->stocks->sum('quantity'),
        ];
    }
}


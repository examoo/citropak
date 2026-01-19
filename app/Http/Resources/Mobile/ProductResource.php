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
            'packing_size' => $this->pieces_per_packing ?? 1,
            'sku' => $this->sku,
            // Pricing
            'list_price' => $this->list_price_before_tax, // Base Price
            'retail_margin' => $this->retail_margin,
            'tp_rate' => $this->tp_rate,
            'dist_margin' => $this->distribution_margin,
            'invoice_price' => $this->invoice_price,
            'unit_price' => $this->unit_price, // Final Unit Price
            // Taxes
            'gst_percent' => $this->fed_sales_tax,
            'fed_percent' => $this->fed_percent,
            // Stock
            'stock_qty' => $this->stocks->sum('balance'), // Simple sum for now
        ];
    }
}

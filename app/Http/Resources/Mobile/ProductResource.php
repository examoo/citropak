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
            'code' => $this->dms_code, // Use dms_code as code
            'sku' => $this->sku,
            'brand' => $this->brand?->name,
            'category' => $this->category?->name,
            'unit_price' => $this->unit_price, // Final price
            'invoice_price' => $this->invoice_price,
            'tax_percent' => $this->fed_sales_tax,
            'packing_size' => $this->pieces_per_packing,
            'stock_qty' => $this->stocks->sum('balance'), // Sum from loaded stocks
        ];
    }
}

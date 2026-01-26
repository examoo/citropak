<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name, // Owner Name
            'shop_name' => $this->shop_name,
            'code' => $this->customer_code,
            'address' => $this->address,
            'phone' => $this->phone ?? $this->mobile_number,
            'ntn' => $this->ntn_number,
            'cnic' => $this->cnic,
            'sales_tax_status' => $this->sales_tax_status, // filer/non-filer
            'balance' => $this->opening_balance ?? 0.0,
            'route_id' => $this->route_id, // If strictly mapped
            'visit_day' => $this->day,
            'channel_id' => $this->channel_id,
            'sub_distribution_id' => $this->sub_distribution_id,
            'category' => $this->category,
            'sub_address' => $this->sub_address,
            'contact' => $this->contact,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}

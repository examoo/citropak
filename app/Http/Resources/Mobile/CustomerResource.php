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
            'code' => $this->customer_code,
            'shop_name' => $this->shop_name,
            'owner_name' => $this->shop_name, // Fallback if owner_name missing
            'address' => $this->address,
            'phone' => $this->phone,
            'latitude' => null, // Add if available in future
            'longitude' => null,
            'balance' => $this->opening_balance, // Should ideally be current balance
            'route_id' => $this->route, // Check if this is relation or string
            'day' => $this->day,
        ];
    }
}

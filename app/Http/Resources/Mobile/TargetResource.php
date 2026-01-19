<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'month' => $this->month,
            'target_amount' => $this->target_amount,
            'achieved_amount' => 0, // Calculate real-time if possible, or send 0
            'brand_targets' => $this->brand_targets,
        ];
    }
}

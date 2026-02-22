<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_points' => $this->total_points,
            'lifetime_points' => $this->lifetime_points,
        ];
    }
}

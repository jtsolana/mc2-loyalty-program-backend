<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loyverse_receipt_id' => $this->loyverse_receipt_id,
            'total_amount' => $this->total_amount,
            'points_earned' => $this->points_earned,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashed_id,
            'status' => $this->status,
            'points_deducted' => $this->points_deducted,
            'expires_at' => $this->expires_at,
            'claimed_at' => $this->claimed_at,
            'created_at' => $this->created_at,
            'reward_details' => $this->resolvePurchaseItems(),
        ];
    }

    /** @return array<int, mixed> */
    private function resolvePurchaseItems(): array
    {
        if ($this->purchase === null || $this->purchase->loyverse_payload === null) {
            return [];
        }

        return $this->purchase->loyverse_payload['line_items'] ?? [];
    }
}

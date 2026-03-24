<?php

namespace App\Http\Resources\Api;

use App\Models\Purchase;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'points' => $this->points,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'purchase_items' => $this->resolvePurchaseItems(),
        ];
    }

    /** @return array<int, mixed> */
    private function resolvePurchaseItems(): array
    {
        if ($this->reference_type === Purchase::class) {
            return $this->reference->loyverse_payload['line_items'] ?? [];
        }

        if ($this->reference_type === Reward::class) {
            $reward = $this->reference;
            if ($reward->purchase !== null) {
                return $reward->purchase->loyverse_payload['line_items'] ?? [];
            }
        }

        return [];

    }
}

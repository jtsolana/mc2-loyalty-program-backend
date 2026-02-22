<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hashed_id,
            'name' => $this->name,
            'reward_title' => $this->reward_title,
            'points_required' => $this->points_required,
            'expires_in_days' => $this->expires_in_days,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}

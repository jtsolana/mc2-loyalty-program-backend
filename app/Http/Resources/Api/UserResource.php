<?php

namespace App\Http\Resources\Api;

use App\Models\RewardRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'hashed_id' => $this->hashed_id,
            'email_verified_at' => $this->email_verified_at,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'loyalty_point' => new LoyaltyPointResource($this->whenLoaded('loyaltyPoint')),
            'reward_progress' => $this->whenLoaded('loyaltyPoint', function () {
                $totalPoints = $this->loyaltyPoint->total_points;

                return RewardRule::where('is_active', true)
                    ->get()
                    ->map(fn ($rule) => [
                        'rule_id' => $rule->hashed_id,
                        'name' => $rule->name,
                        'reward_title' => $rule->reward_title,
                        'points_required' => $rule->points_required,
                        'current_points' => $totalPoints,
                        'points_remaining' => max(0, $rule->points_required - $totalPoints),
                        'progress_percentage' => min(100, (int) round($totalPoints / $rule->points_required * 100)),
                    ])
                    ->values();
            }),
            'created_at' => $this->created_at,
        ];
    }
}

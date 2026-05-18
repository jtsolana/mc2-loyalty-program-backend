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
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'avatar' => $this->avatar,
            'hashed_id' => $this->hashed_id,
            'email_verified_at' => $this->email_verified_at,
            'is_birthday_today' => $this->isBirthdayRewardClaimable(),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'loyalty_point' => new LoyaltyPointResource($this->whenLoaded('loyaltyPoint')),
            'reward_progress' => $this->whenLoaded('loyaltyPoint', function () {
                $totalPoints = $this->loyaltyPoint->total_points;

                return RewardRule::where('is_active', true)
                    ->get()
                    ->filter(fn (RewardRule $rule) => $rule->isApplicableToUser($this->resource))
                    ->map(fn ($rule) => [
                        'rule_id' => $rule->hashed_id,
                        'name' => $rule->name,
                        'reward_title' => $rule->reward_title,
                        'points_required' => $rule->points_required,
                        'current_points' => $totalPoints,
                        'redeemable_reward' => $rule->points_required > 0
                            ? (int) floor($totalPoints / $rule->points_required)
                            : 1,
                        'points_remaining' => max(0, $rule->points_required - $totalPoints)
                    ])
                    ->values();
            }),
            'todays_reward_limit_reached' => $this->todaysRewardLimitReached,
            'created_at' => $this->created_at,
        ];
    }
}

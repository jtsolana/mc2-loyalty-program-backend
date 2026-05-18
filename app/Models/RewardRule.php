<?php

namespace App\Models;

use App\Enums\RewardRuleType;
use App\Traits\HashTrait;
use Database\Factories\RewardRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardRule extends Model
{
    /** @use HasFactory<RewardRuleFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'name',
        'reward_title',
        'points_required',
        'expires_in_days',
        'is_active',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'points_required' => 'integer',
            'expires_in_days' => 'integer',
            'is_active' => 'boolean',
            'type' => RewardRuleType::class,
        ];
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function isApplicableToUser(User $user): bool
    {
        return match ($this->type) {
            RewardRuleType::PointsBased => $this->checkPointsRequired($user),
            default => false,
        };
    }

    public function isApplicableToBirthdayUser(User $user): bool
    {
        return match ($this->type) {
            RewardRuleType::Birthday => $user->isBirthdayRewardClaimable(),
            default => false,
        };
    }

    private function checkPointsRequired(User $user): bool
    {
        return $user->loyaltyPoint?->total_points >= $this->points_required;
    }
}

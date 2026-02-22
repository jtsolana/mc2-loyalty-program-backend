<?php

namespace App\Models;

use App\Enums\RewardStatus;
use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Reward extends Model
{
    /** @use HasFactory<\Database\Factories\RewardFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'user_id',
        'reward_rule_id',
        'staff_id',
        'points_deducted',
        'status',
        'expires_at',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'points_deducted' => 'integer',
            'status' => RewardStatus::class,
            'expires_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rewardRule(): BelongsTo
    {
        return $this->belongsTo(RewardRule::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function pointTransactions(): MorphMany
    {
        return $this->morphMany(PointTransaction::class, 'reference');
    }
}

<?php

namespace App\Models;

use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardRule extends Model
{
    /** @use HasFactory<\Database\Factories\RewardRuleFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'name',
        'reward_title',
        'points_required',
        'expires_in_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points_required' => 'integer',
            'expires_in_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }
}

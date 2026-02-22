<?php

namespace App\Models;

use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyPoint extends Model
{
    /** @use HasFactory<\Database\Factories\LoyaltyPointFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'user_id',
        'total_points',
        'lifetime_points',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'integer',
            'lifetime_points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class, 'user_id', 'user_id');
    }
}

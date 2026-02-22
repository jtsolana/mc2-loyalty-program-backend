<?php

namespace App\Models;

use App\Enums\RedemptionStatus;
use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Redemption extends Model
{
    /** @use HasFactory<\Database\Factories\RedemptionFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'user_id',
        'staff_id',
        'purchase_id',
        'points_used',
        'discount_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'points_used' => 'integer',
            'discount_amount' => 'decimal:2',
            'status' => RedemptionStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function pointTransactions(): MorphMany
    {
        return $this->morphMany(PointTransaction::class, 'reference');
    }
}

<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'user_id',
        'loyverse_receipt_id',
        'loyverse_customer_id',
        'total_amount',
        'points_earned',
        'status',
        'loyverse_payload',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'points_earned' => 'integer',
            'status' => PurchaseStatus::class,
            'loyverse_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function pointTransactions(): MorphMany
    {
        return $this->morphMany(PointTransaction::class, 'reference');
    }
}

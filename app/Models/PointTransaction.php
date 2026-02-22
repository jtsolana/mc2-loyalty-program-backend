<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PointTransactionFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'user_id',
        'staff_id',
        'type',
        'points',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'points' => 'integer',
            'balance_after' => 'integer',
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

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

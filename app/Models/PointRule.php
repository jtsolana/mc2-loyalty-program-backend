<?php

namespace App\Models;

use App\Enums\PointRuleType;
use App\Traits\HashTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    /** @use HasFactory<\Database\Factories\PointRuleFactory> */
    use HasFactory, HashTrait;

    protected $fillable = [
        'name',
        'type',
        'spend_amount',
        'minimum_spend',
        'points_per_unit',
        'points_per_item',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PointRuleType::class,
            'spend_amount' => 'decimal:2',
            'minimum_spend' => 'decimal:2',
            'points_per_unit' => 'integer',
            'points_per_item' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calculate points earned based on the rule type.
     *
     * @param  float  $amount  Total amount spent (used for spend_based rules).
     * @param  int  $itemCount  Number of items/drinks (used for per_item rules).
     */
    public function calculatePoints(float $amount = 0, int $itemCount = 0): int
    {
        if ($this->type === PointRuleType::PerItem) {
            return $itemCount * ($this->points_per_item ?? 1);
        }

        // spend_based (default)
        if ($amount < $this->minimum_spend) {
            return 0;
        }

        return (int) floor($amount / $this->spend_amount) * $this->points_per_unit;
    }
}

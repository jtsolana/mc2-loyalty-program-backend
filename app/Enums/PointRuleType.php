<?php

namespace App\Enums;

enum PointRuleType: string
{
    case SpendBased = 'spend_based';
    case PerItem = 'per_item';

    public function label(): string
    {
        return match ($this) {
            self::SpendBased => 'Per Spend Amount',
            self::PerItem => 'Per Item / Drink',
        };
    }
}

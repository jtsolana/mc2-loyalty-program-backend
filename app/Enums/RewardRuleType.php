<?php

namespace App\Enums;

enum RewardRuleType: string
{
    case PointsBased = 'points_based';
    case Birthday = 'birthday';

    public function label(): string
    {
        return match ($this) {
            self::PointsBased => 'Points Based',
            self::Birthday => 'Birthday',
        };
    }
}

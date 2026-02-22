<?php

namespace App\Enums;

enum RewardStatus: string
{
    case Pending = 'pending';
    case Claimed = 'claimed';
    case Expired = 'expired';
}

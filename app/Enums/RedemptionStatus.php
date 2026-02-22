<?php

namespace App\Enums;

enum RedemptionStatus: string
{
    case Pending = 'pending';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
}

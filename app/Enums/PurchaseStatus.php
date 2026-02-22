<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}

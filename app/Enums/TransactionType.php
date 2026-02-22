<?php

namespace App\Enums;

enum TransactionType: string
{
    case Earn = 'earn';
    case Redeem = 'redeem';
    case Reward = 'reward';
    case Expire = 'expire';
    case Adjust = 'adjust';
}

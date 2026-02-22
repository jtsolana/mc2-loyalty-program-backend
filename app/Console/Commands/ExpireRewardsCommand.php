<?php

namespace App\Console\Commands;

use App\Enums\RewardStatus;
use App\Models\Reward;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireRewardsCommand extends Command
{
    protected $signature = 'rewards:expire';

    protected $description = 'Mark pending rewards as expired when their expiration date has passed';

    public function handle(): int
    {
        $count = Reward::where('status', RewardStatus::Pending)
            ->where('expires_at', '<', Carbon::now())
            ->update(['status' => RewardStatus::Expired]);

        $this->info("Expired {$count} reward(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\AwardBirthdayPointsJob;
use Illuminate\Console\Command;

class AwardBirthdayPointsCommand extends Command
{
    protected $signature = 'customers:birthday-rewards';

    protected $description = 'Dispatch a job that awards birthday bonus points to qualifying customers.';

    public function handle(): int
    {
        AwardBirthdayPointsJob::dispatch()->onQueue('loyverse');

        $this->info('Birthday rewards job dispatched.');

        return self::SUCCESS;
    }
}

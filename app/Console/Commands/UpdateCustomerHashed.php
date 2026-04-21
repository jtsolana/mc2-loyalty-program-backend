<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Jobs\UpdateCustomerHashedJob;

#[Signature('mc2:update-customer-hashed {--no-loyverse-id : Only query customers with no loyverse_customer_id}')]
#[Description('Update customers with hashed IDs and sync with Loyverse')]
class UpdateCustomerHashed extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $onlyWithoutLoyverseId = $this->option('no-loyverse-id');
        UpdateCustomerHashedJob::dispatch($onlyWithoutLoyverseId)->onQueue('loyverse');
    }
}

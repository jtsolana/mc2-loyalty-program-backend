<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Jobs\UpdateCustomerHashedJob;

#[Signature('mc2:update-customer-hashed')]
#[Description('Update customers with hashed IDs and sync with Loyverse')]
class UpdateCustomerHashed extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        UpdateCustomerHashedJob::dispatch()->onQueue('loyverse');
    }
}

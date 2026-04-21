<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Database\Eloquent\Collection;


class UpdateIndividualCustomerHashedJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private Collection $customers)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->customers as $customer) {
            UpdateIndividualCustomerLoyverseJob::dispatch($customer)->onQueue('loyverse');
        }
    }
}

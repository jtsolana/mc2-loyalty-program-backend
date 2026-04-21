<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;

class UpdateCustomerHashedJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public bool $onlyWithoutLoyverseId = false)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = User::whereHas('roles', fn ($q) => $q->where('name', 'customer'));

        if ($this->onlyWithoutLoyverseId) {
            $query->whereNull('loyverse_customer_id');
        }

        $query->chunk(100, function ($customers) {
            UpdateIndividualCustomerHashedJob::dispatch($customers)->onQueue('loyverse');
        });
    }
}

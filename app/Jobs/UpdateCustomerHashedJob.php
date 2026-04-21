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
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->chunk(100, function ($customers) {
                UpdateIndividualCustomerHashedJob::dispatch($customers)->onQueue('loyverse');
            });
    }
}

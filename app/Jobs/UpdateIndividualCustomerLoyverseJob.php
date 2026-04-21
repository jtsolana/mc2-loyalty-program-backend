<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\LoyverseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class UpdateIndividualCustomerLoyverseJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private User $customer)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = [
            'name' => '---------------------',
            'email' => Str::lower(Str::toBase64($this->customer->email)).'@mc2.com',
            'phone' => '00000000000',
            'customer_code' => $this->customer->hashed_id,
        ];

        if ($this->customer->loyverse_customer_id) {
            $data['id'] = $this->customer->loyverse_customer_id;
        }

        $loyverseService = new LoyverseService();

        $loyverseId = $loyverseService->createCustomer($data);

        if ($loyverseId) {
            $this->customer->update(['loyverse_customer_id' => $loyverseId]);
        }

        Log::info("Updated customer {$this->customer->id} with hashed ID {$this->customer->hashed_id} and Loyverse ID {$loyverseId}");
    }
}

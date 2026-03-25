<?php

namespace App\Jobs;

use App\Enums\RewardStatus;
use App\Models\Reward;
use App\Models\User;
use App\Services\LoyverseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class CreateLoyverseRewardReceipt implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Reward $reward,
        private User $user,
        private string $loyverseVariantId,
        private int $claimAmount = 1,
    ) {}

    public function handle(LoyverseService $loyverseService): void
    {
        $customerId = $this->user->loyverse_customer_id;

        $loyverseItems = $loyverseService->createRewardReceipt($this->reward, $customerId, $this->loyverseVariantId, $this->claimAmount);

        $this->reward->update([
            'status' => RewardStatus::Claimed,
            'staff_id' => $this->user->id,
            'claimed_at' => Carbon::now(),
        ]);

        collect($loyverseItems)->map(function ($item) {
            $mobileScheme = config('app.mobile_scheme');

            SendPushNotificationToCustomers::dispatch(
                '🎉 Reward Claimed!',
                "You have successfully claimed: {$item['item_name']} {$item['variant_name']} ({$this->claimAmount}x)",
                [
                    'type' => 'reward',
                    'user_id' => (string) $this->user->hashed_id,
                    'deep_link' => "{$mobileScheme}rewards",
                ],
                $this->user->id
            )->onQueue('loyverse');
        });
    }
}

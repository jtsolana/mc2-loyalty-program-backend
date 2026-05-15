<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class SendBirthdayGreetingsJob implements ShouldQueue
{
    use Queueable;

    private const MIN_LIFETIME_POINTS = 10;

    public function handle(): void
    {
        $today = Carbon::today();

        $rollOverFeb29 = $today->month === 2 && $today->day === 28 && ! $today->isLeapYear();

        $customers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->whereNotNull('date_of_birth')
            ->where(function ($q) use ($today, $rollOverFeb29) {
                $q->where(fn ($q) => $q->whereMonth('date_of_birth', $today->month)
                    ->whereDay('date_of_birth', $today->day));

                if ($rollOverFeb29) {
                    $q->orWhere(fn ($q) => $q->whereMonth('date_of_birth', 2)
                        ->whereDay('date_of_birth', 29));
                }
            })
            ->get();

        foreach ($customers as $customer) {
            if($customer->isBirthdayToday()) {
                $mobileScheme = config('app.mobile_scheme');

                SendPushNotificationToCustomers::dispatch(
                    "Happy Birthday {$customer->name}! 🎉",
                    "Visit us today and enjoy your birthday reward.",
                    [
                        'type' => 'birthday_greeting',
                        'user_id' => (string) $customer->hashed_id,
                        'deep_link' => "{$mobileScheme}rewards",
                    ]
                )->onQueue('loyverse');
            }
        }
    }
}

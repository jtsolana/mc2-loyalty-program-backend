<?php

namespace App\Jobs;

use App\Enums\TransactionType;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AwardBirthdayPointsJob implements ShouldQueue
{
    use Queueable;

    private const BIRTHDAY_DESCRIPTION = 'Happy Birthday! Enjoy 10 bonus points 🎉';

    private const MIN_LIFETIME_POINTS = 10;

    private const REWARD_POINTS = 10;

    public function handle(PointService $pointService): void
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
            ->whereHas('loyaltyPoint', fn ($q) => $q->where('lifetime_points', '>=', self::MIN_LIFETIME_POINTS))
            ->with('loyaltyPoint')
            ->get();

        $awarded = 0;

        foreach ($customers as $customer) {
            $alreadyAwarded = PointTransaction::query()
                ->where('user_id', $customer->id)
                ->where('type', TransactionType::Earn->value)
                ->where('description', self::BIRTHDAY_DESCRIPTION)
                ->where('created_at', '>=', $today->copy()->startOfDay())
                ->exists();

            if ($alreadyAwarded) {
                continue;
            }

            $pointService->earnPoints(
                customer: $customer,
                points: self::REWARD_POINTS,
                description: self::BIRTHDAY_DESCRIPTION,
            );

            $awarded++;
        }

        Log::info("Birthday rewards awarded to {$awarded} customer(s) on {$today->toDateString()}.");
    }
}

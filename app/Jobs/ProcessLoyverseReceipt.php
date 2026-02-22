<?php

namespace App\Jobs;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessLoyverseReceipt implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly array $receipt) {}

    public function handle(PointService $pointService): void
    {
        if (($this->receipt['receipt_type'] ?? null) !== 'SALE') {
            return;
        }

        $receiptId = $this->receipt['receipt_number'] ?? null;

        if (! $receiptId) {
            return;
        }

        if (Purchase::where('loyverse_receipt_id', $receiptId)->exists()) {
            return;
        }

        $totalMoney = (float) ($this->receipt['total_money'] ?? 0);
        $loyverseCustomerId = $this->receipt['customer_id'] ?? null;
        $lineItems = $this->receipt['line_items'] ?? [];
        $itemCount = (int) collect($lineItems)->sum('quantity');

        $customer = $loyverseCustomerId
            ? User::where('loyverse_customer_id', $loyverseCustomerId)->first()
            : null;

        $pointsEarned = $customer
            ? $pointService->calculatePoints($totalMoney, $itemCount)
            : 0;

        $purchase = Purchase::create([
            'user_id' => $customer?->id,
            'loyverse_receipt_id' => $receiptId,
            'loyverse_customer_id' => $loyverseCustomerId,
            'total_amount' => $totalMoney,
            'points_earned' => $pointsEarned,
            'status' => PurchaseStatus::Completed->value,
            'loyverse_payload' => $this->receipt,
        ]);

        if ($customer && $pointsEarned > 0) {
            $pointService->earnPoints(
                customer: $customer,
                points: $pointsEarned,
                description: "Earned {$pointsEarned} points from purchase #{$receiptId}".($itemCount > 0 ? " ({$itemCount} items)" : ''),
                purchase: $purchase,
            );
        }

        Log::info('Loyverse receipt processed', [
            'receipt_id' => $receiptId,
            'customer_id' => $loyverseCustomerId,
            'points_earned' => $pointsEarned,
        ]);
    }
}

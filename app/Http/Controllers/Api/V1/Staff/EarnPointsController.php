<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Staff\EarnPointsRequest;
use App\Http\Resources\Api\PointTransactionResource;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\JsonResponse;

class EarnPointsController extends Controller
{
    public function __construct(private readonly PointService $pointService) {}

    public function __invoke(EarnPointsRequest $request): JsonResponse
    {
        $customer = User::findOrFail($request->input('user_id'));
        $purchase = $request->filled('purchase_id')
            ? Purchase::findOrFail($request->input('purchase_id'))
            : null;

        $amountSpent = (float) ($request->input('amount_spent', 0));
        $itemCount = (int) ($request->input('item_count', 0));

        $points = $this->pointService->calculatePoints($amountSpent, $itemCount);

        if ($points === 0) {
            return response()->json(['message' => 'No active point rule found or no points earned.'], 422);
        }

        $defaultDescription = $itemCount > 0
            ? "Earned {$points} points for {$itemCount} item(s)"
            : "Earned {$points} points for ₱{$amountSpent} spend";

        $transaction = $this->pointService->earnPoints(
            customer: $customer,
            points: $points,
            description: $request->input('description', $defaultDescription),
            staff: $request->user(),
            purchase: $purchase,
        );

        return response()->json([
            'message' => "Successfully awarded {$points} points.",
            'data' => new PointTransactionResource($transaction),
        ], 201);
    }
}

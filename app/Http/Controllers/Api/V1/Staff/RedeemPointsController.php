<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Staff\RedeemPointsRequest;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\JsonResponse;

class RedeemPointsController extends Controller
{
    public function __construct(private readonly PointService $pointService) {}

    public function __invoke(RedeemPointsRequest $request): JsonResponse
    {
        $customer = User::findOrFail($request->input('user_id'));
        $purchase = $request->filled('purchase_id')
            ? Purchase::findOrFail($request->input('purchase_id'))
            : null;

        try {
            $redemption = $this->pointService->redeemPoints(
                customer: $customer,
                points: $request->input('points_to_redeem'),
                staff: $request->user(),
                purchase: $purchase,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Successfully redeemed {$request->input('points_to_redeem')} points for ₱{$redemption->discount_amount} discount.",
            'data' => [
                'redemption_id' => $redemption->id,
                'points_used' => $redemption->points_used,
                'discount_amount' => $redemption->discount_amount,
                'status' => $redemption->status,
            ],
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LoyaltyPointResource;
use App\Http\Resources\Api\PointTransactionResource;
use App\Models\LoyaltyPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $loyaltyPoint = $request->user()->loyaltyPoint
            ?? LoyaltyPoint::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['total_points' => 0, 'lifetime_points' => 0]
            );

        return response()->json(['data' => new LoyaltyPointResource($loyaltyPoint)]);
    }

    public function history(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->pointTransactions()
            ->orderByDesc('id')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => PointTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}

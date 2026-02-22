<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RewardResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rewards = $request->user()
            ->rewards()
            ->with('rewardRule')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => RewardResource::collection($rewards),
            'meta' => [
                'current_page' => $rewards->currentPage(),
                'last_page' => $rewards->lastPage(),
                'total' => $rewards->total(),
            ],
        ]);
    }
}

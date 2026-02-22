<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PromotionResource;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Promotion::query()->published()->latest();

        if ($request->filled('type') && in_array($request->input('type'), ['promotion', 'announcement'])) {
            $query->where('type', $request->input('type'));
        }

        $promotions = $query->paginate(20);

        return response()->json([
            'data' => PromotionResource::collection($promotions),
            'meta' => [
                'current_page' => $promotions->currentPage(),
                'last_page' => $promotions->lastPage(),
                'per_page' => $promotions->perPage(),
                'total' => $promotions->total(),
            ],
        ]);
    }

    public function show(Promotion $promotion): JsonResponse
    {
        if (! $promotion->is_published) {
            abort(404);
        }

        return response()->json([
            'data' => new PromotionResource($promotion),
        ]);
    }
}

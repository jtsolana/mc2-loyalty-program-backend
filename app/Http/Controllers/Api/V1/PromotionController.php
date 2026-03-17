<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PromotionResource;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PromotionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $version = Cache::get('promotions:version', 0);
        $type = $request->input('type', 'all');
        $page = $request->input('page', 1);
        $cacheKey = "promotions:index:v{$version}:{$type}:{$page}";

        return Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Promotion::query()->published()->latest();

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            $promotions = $query->paginate(10);

            return response()->json([
                'data' => PromotionResource::collection($promotions),
                'meta' => [
                    'current_page' => $promotions->currentPage(),
                    'last_page' => $promotions->lastPage(),
                    'per_page' => $promotions->perPage(),
                    'total' => $promotions->total(),
                ],
            ]);
        });
    }

    public function show(Promotion $promotion): JsonResponse
    {
        if (! $promotion->is_published) {
            abort(404);
        }

        $version = Cache::get('promotions:version', 0);
        $cacheKey = "promotions:show:v{$version}:{$promotion->id}";

        return Cache::remember($cacheKey, 1800, function () use ($promotion) {
            return response()->json([
                'data' => new PromotionResource($promotion),
            ]);
        });
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\PointRuleRequest;
use App\Models\PointRule;
use Illuminate\Http\JsonResponse;

class PointRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => PointRule::all()]);
    }

    public function store(PointRuleRequest $request): JsonResponse
    {
        $rule = PointRule::create($request->validated());

        return response()->json(['data' => $rule], 201);
    }

    public function show(PointRule $pointRule): JsonResponse
    {
        return response()->json(['data' => $pointRule]);
    }

    public function update(PointRuleRequest $request, PointRule $pointRule): JsonResponse
    {
        $pointRule->update($request->validated());

        return response()->json(['data' => $pointRule]);
    }

    public function destroy(PointRule $pointRule): JsonResponse
    {
        $pointRule->delete();

        return response()->json(['message' => 'Point rule deleted.']);
    }
}

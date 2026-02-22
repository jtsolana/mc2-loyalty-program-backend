<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('roles.permissions', 'loyaltyPoint')),
            'token' => $token,
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->findByLogin($request->input('login'));

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('roles.permissions', 'loyaltyPoint')),
            'token' => $token,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful. Please check your email for verification.',
        ], 201);
    }
}

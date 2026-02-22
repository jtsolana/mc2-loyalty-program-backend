<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $socialUser = Socialite::driver($provider)->stateless()->userFromToken($request->input('token'));

        $user = $this->authService->findOrCreateSocialUser($socialUser, $provider);
        $token = $user->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('roles.permissions', 'loyaltyPoint')),
            'token' => $token,
        ]);
    }

    private function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'facebook'])) {
            abort(422, "Unsupported provider: {$provider}");
        }
    }
}

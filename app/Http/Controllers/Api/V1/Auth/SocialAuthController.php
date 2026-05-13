<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function redirect(Request $request, string $provider): RedirectResponse  
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->stateless()->redirect();
    }


    public function callback(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->stateless()->user();

            $user = $this->authService->findOrCreateSocialUser($socialiteUser, $provider);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to authenticate with ' . ucfirst($provider)], 422);
        }

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

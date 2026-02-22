<?php

namespace App\Services;

use App\Models\LoyaltyPoint;
use App\Models\Role;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthService
{
    public function __construct(private readonly LoyverseService $loyverseService) {}

    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $customerRole = Role::where('name', 'customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole);
        }

        LoyaltyPoint::create(['user_id' => $user->id]);

        $loyverseId = $this->loyverseService->createCustomer([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'customer_code' => $user->hashed_id,
        ]);

        if ($loyverseId) {
            $user->update(['loyverse_customer_id' => $loyverseId]);
        }

        return $user;
    }

    public function findByLogin(string $login): ?User
    {
        return User::where('email', $login)
            ->orWhere('username', $login)
            ->first();
    }

    public function findOrCreateSocialUser(SocialiteUser $socialUser, string $provider): User
    {
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $socialAccount->update([
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
            ]);

            return $socialAccount->user;
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'password' => null,
            ]);

            $customerRole = Role::where('name', 'customer')->first();
            if ($customerRole) {
                $user->roles()->attach($customerRole);
            }

            LoyaltyPoint::create(['user_id' => $user->id]);

            $loyverseId = $this->loyverseService->createCustomer([
                'name' => $user->name,
                'email' => $user->email,
                'customer_code' => $user->hashed_id,
            ]);

            if ($loyverseId) {
                $user->update(['loyverse_customer_id' => $loyverseId]);
            }
        }

        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_token' => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken,
        ]);

        return $user;
    }
}

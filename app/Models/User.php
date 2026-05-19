<?php

namespace App\Models;

use App\Traits\HashTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HashTrait, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    private const MIN_LIFETIME_POINTS = 10;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'date_of_birth',
        'avatar',
        'loyverse_customer_id',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function loyaltyPoint(): HasOne
    {
        return $this->hasOne(LoyaltyPoint::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function isBirthdayRewardClaimable(): bool
    {
        if ($this->loyaltyPoint?->lifetime_points < self::MIN_LIFETIME_POINTS) {
            return false;
        }

        $dob = $this->date_of_birth;
        if (! $dob) {
            return false;
        }

        $today = Carbon::today();

        $birthday = ($dob->month === 2 && $dob->day === 29 && ! $today->isLeapYear())
            ? Carbon::create($today->year, 2, 28)
            : Carbon::create($today->year, $dob->month, $dob->day);

        $birthdayRewardRuleId = config('app.birthday_reward_rule_id');
        $birthdayRewardRule = RewardRule::find($birthdayRewardRuleId);
        if (! $birthdayRewardRule) {
            return false;
        }

        $windowStart = $birthday->copy()->startOfDay();
        $windowEnd = $birthday->copy()->addDays($birthdayRewardRule->expires_in_days - 1)->endOfDay();

        if (! $today->betweenIncluded($windowStart, $windowEnd)) {
            return false;
        }

        return ! $this->rewards()
            ->where('reward_rule_id', $birthdayRewardRuleId)
            ->where('status', 'claimed')
            ->whereBetween('claimed_at', [$windowStart, $windowEnd])
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function todaysRewardLimitReached(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->rewards()
                ->whereDate('claimed_at', today())
                ->count() >= (int) config('app.reward_redemption_limit_per_day'),
        );
    }
}

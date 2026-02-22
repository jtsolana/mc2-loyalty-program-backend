<?php

use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\PointRuleController;
use App\Http\Controllers\Api\V1\Admin\RewardRuleController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Customer\PointController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use App\Http\Controllers\Api\V1\Customer\RewardController;
use App\Http\Controllers\Api\V1\Loyverse\WebhookController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\Staff\ClaimRewardController;
use App\Http\Controllers\Api\V1\Staff\EarnPointsController;
use App\Http\Controllers\Api\V1\Staff\RedeemPointsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
        Route::post('social/{provider}', SocialAuthController::class);
        Route::post('logout', LogoutController::class)->middleware('auth:sanctum');
    });

    Route::middleware(['auth:sanctum', 'permission:points.view'])->prefix('customer')->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::get('points', [PointController::class, 'show']);
        Route::get('points/history', [PointController::class, 'history']);
        Route::get('rewards', [RewardController::class, 'index']);
    });

    // Route::middleware(['auth:sanctum', 'permission:points.earn'])->prefix('staff')->group(function () {
    //     Route::post('earn-points', EarnPointsController::class);
    // });

    Route::middleware(['auth:sanctum', 'permission:points.redeem'])->prefix('staff')->group(function () {
        // Route::post('redeem-points', RedeemPointsController::class);
        Route::get('customers/{user}/rewards', [ClaimRewardController::class, 'customerRewards']);
        Route::post('rewards/{reward}/claim', [ClaimRewardController::class, 'claim']);
    });

    Route::post('loyverse/webhook', WebhookController::class);

    Route::get('promotions', [PromotionController::class, 'index']);
    Route::get('promotions/{promotion}', [PromotionController::class, 'show']);

    // Route::middleware(['auth:sanctum', 'permission:customers.manage'])->prefix('admin')->group(function () {
    //     Route::get('customers', [CustomerController::class, 'index']);
    //     Route::get('customers/{user}', [CustomerController::class, 'show']);
    // });

    // Route::middleware(['auth:sanctum', 'permission:point-rules.manage'])->prefix('admin')->group(function () {
    //     Route::get('point-rules', [PointRuleController::class, 'index']);
    //     Route::post('point-rules', [PointRuleController::class, 'store']);
    //     Route::get('point-rules/{pointRule}', [PointRuleController::class, 'show']);
    //     Route::put('point-rules/{pointRule}', [PointRuleController::class, 'update']);
    //     Route::delete('point-rules/{pointRule}', [PointRuleController::class, 'destroy']);
    // });

    // Route::middleware(['auth:sanctum', 'permission:reward-rules.manage'])->prefix('admin')->group(function () {
    //     Route::get('reward-rules', [RewardRuleController::class, 'index']);
    //     Route::post('reward-rules', [RewardRuleController::class, 'store']);
    //     Route::get('reward-rules/{rewardRule}', [RewardRuleController::class, 'show']);
    //     Route::put('reward-rules/{rewardRule}', [RewardRuleController::class, 'update']);
    //     Route::delete('reward-rules/{rewardRule}', [RewardRuleController::class, 'destroy']);
    // });

    // Route::middleware(['auth:sanctum', 'permission:roles.manage'])->prefix('admin')->group(function () {
    //     Route::get('roles', [RoleController::class, 'index']);
    //     Route::post('roles', [RoleController::class, 'store']);
    //     Route::get('roles/{role}', [RoleController::class, 'show']);
    //     Route::put('roles/{role}', [RoleController::class, 'update']);
    //     Route::delete('roles/{role}', [RoleController::class, 'destroy']);
    //     Route::get('roles/{role}/permissions', [RoleController::class, 'permissions']);
    //     Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
    // });
});

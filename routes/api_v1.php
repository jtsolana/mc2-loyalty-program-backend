<?php

use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\CompanyProfileController;
use App\Http\Controllers\Api\V1\Customer\PointController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use App\Http\Controllers\Api\V1\Customer\RewardController;
use App\Http\Controllers\Api\V1\Loyverse\WebhookController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\Staff\ClaimRewardController;
use App\Http\Controllers\Api\V1\UserDeviceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
        Route::post('social/{provider}', SocialAuthController::class);
        Route::post('logout', LogoutController::class)->middleware('auth:sanctum');
        Route::post('forgot-password', ForgotPasswordController::class);
        Route::post('reset-password', ResetPasswordController::class);
        Route::post('change-password', ChangePasswordController::class)->middleware('auth:sanctum');
    });

    Route::middleware(['auth:sanctum', 'permission:points.view'])->prefix('customer')->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::get('points', [PointController::class, 'show']);
        Route::get('points/history', [PointController::class, 'history']);
        Route::get('rewards', [RewardController::class, 'index']);
        Route::post('deactivate', [ProfileController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'permission:points.redeem'])->prefix('staff')->group(function () {
        Route::get('customers/{user}/rewards', [ClaimRewardController::class, 'customerRewards']);
        Route::post('rewards/{user}/claim', [ClaimRewardController::class, 'claim']);
        Route::get('items', [ClaimRewardController::class, 'getItems']);
    });

    Route::post('loyverse/webhook', WebhookController::class);

    Route::get('promotions', [PromotionController::class, 'index']);
    Route::get('promotions/{promotion}', [PromotionController::class, 'show']);

    Route::get('terms', [CompanyProfileController::class, 'terms']);

    Route::middleware('auth:sanctum')->prefix('devices')->group(function () {
        Route::post('register', [UserDeviceController::class, 'register']);
        Route::post('unregister', [UserDeviceController::class, 'unregister']);
    });
});

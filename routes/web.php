<?php

use App\Http\Controllers\Admin\CompanyProfileController as AdminCompanyProfileController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PointRuleController as AdminPointRuleController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\RewardRuleController as AdminRewardRuleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify.mobile');

Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');

Route::redirect('dashboard', '/admin')->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'verified', 'permission:roles.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{user}', [AdminCustomerController::class, 'show'])->name('customers.show');

    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('point-rules', [AdminPointRuleController::class, 'index'])->name('point-rules.index');
    Route::post('point-rules', [AdminPointRuleController::class, 'store'])->name('point-rules.store');
    Route::put('point-rules/{pointRule}', [AdminPointRuleController::class, 'update'])->name('point-rules.update');
    Route::delete('point-rules/{pointRule}', [AdminPointRuleController::class, 'destroy'])->name('point-rules.destroy');

    Route::get('reward-rules', [AdminRewardRuleController::class, 'index'])->name('reward-rules.index');
    Route::post('reward-rules', [AdminRewardRuleController::class, 'store'])->name('reward-rules.store');
    Route::put('reward-rules/{rewardRule}', [AdminRewardRuleController::class, 'update'])->name('reward-rules.update');
    Route::delete('reward-rules/{rewardRule}', [AdminRewardRuleController::class, 'destroy'])->name('reward-rules.destroy');

    Route::get('promotions', [AdminPromotionController::class, 'index'])->name('promotions.index');
    Route::post('promotions', [AdminPromotionController::class, 'store'])->name('promotions.store');
    Route::post('promotions/{promotion}', [AdminPromotionController::class, 'update'])->name('promotions.update');
    Route::delete('promotions/{promotion}', [AdminPromotionController::class, 'destroy'])->name('promotions.destroy');

    Route::get('company-profile', [AdminCompanyProfileController::class, 'edit'])->name('company-profile.edit');
    Route::post('company-profile', [AdminCompanyProfileController::class, 'update'])->name('company-profile.update');

    Route::get('firebase-messaging-test', function () {
        $messaging = Firebase::messaging();

        $message = CloudMessage::new()
            ->withToken(env('TEST_DEVICE_FCM_TOKEN'))
            ->withNotification(
                Notification::create(
                    '🎉 Reward Unlocked!',
                    'You are now eligible to claim your reward!'
                )
            );
        $messaging->send($message);

        return 'Test notification sent!';
    })->name('firebase-messaging-test');
});



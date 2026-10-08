<?php

use App\Http\Controllers\Saas\AccountController;
use App\Http\Controllers\Saas\FileController;
use App\Http\Controllers\Saas\PlatformController;
use App\Http\Controllers\Saas\SignupController;
use App\Http\Controllers\Saas\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/pricing', [SignupController::class, 'pricing'])->name('saas.pricing');
Route::get('/register', [SignupController::class, 'form'])->name('register');
Route::post('/register', [SignupController::class, 'store'])->middleware('throttle:5,1');
Route::get('/saas/workspace/{slug}', [SignupController::class, 'workspace'])->name('saas.workspace');
Route::get('/saas-files/{workspace}/{path}', [FileController::class, 'show'])->where('path', '.*')->name('saas.files');
Route::middleware('auth')->group(function () {
    Route::get('/account/integrations', [\App\Http\Controllers\Saas\IntegrationsController::class, 'index'])->name('saas.integrations');
    Route::post('/account/integrations', [AccountController::class, 'saveIntegrations'])->name('saas.integrations.save');
    Route::get('/account/subscription', [\App\Http\Controllers\Saas\SubscriptionController::class, 'index'])->name('saas.subscription');
    Route::post('/account/subscription/request-plan', [AccountController::class, 'requestPlan'])->middleware('throttle:5,60')->name('saas.request-plan');
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])->middleware('throttle:3,10')->name('verification.send');
});
Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('login', [PlatformController::class, 'loginForm'])->name('login');
    Route::post('login', [PlatformController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware('auth:platform')->group(function () {
        Route::get('/', [PlatformController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [PlatformController::class, 'logout'])->name('logout');
        Route::get('tenants/{tenant}', [PlatformController::class, 'tenant'])->name('tenants.show');
        Route::delete('tenants/{tenant}', [PlatformController::class, 'deleteTenant'])->middleware('throttle:5,1')->name('tenants.destroy');
        Route::put('tenants/{tenant}', [PlatformController::class, 'updateTenant'])->name('tenants.update');
        Route::post('tenants/{tenant}/extend', [PlatformController::class, 'extend'])->name('tenants.extend');
        Route::post('tenants/{tenant}/plan', [PlatformController::class, 'changePlan'])->name('tenants.plan');
        Route::post('tenants/{tenant}/verify-owner', [PlatformController::class, 'verifyOwner'])->name('tenants.verify-owner');
        Route::get('plans', [PlatformController::class, 'plans'])->name('plans');
        Route::post('plans', [PlatformController::class, 'savePlan'])->name('plans.store');
        Route::put('plans/{plan}', [PlatformController::class, 'savePlan'])->name('plans.update');
        Route::get('profile', [PlatformController::class, 'profile'])->name('profile');
        Route::put('profile', [PlatformController::class, 'updateProfile'])->middleware('throttle:10,1')->name('profile.update');
        Route::put('profile/password', [PlatformController::class, 'updatePassword'])->middleware('throttle:5,1')->name('profile.password');
        Route::get('admins', [PlatformController::class, 'admins'])->name('admins');
        Route::post('admins', [PlatformController::class, 'createAdmin'])->middleware('throttle:5,1')->name('admins.store');
        Route::post('settings/telephony', [PlatformController::class, 'saveTelephony'])->name('telephony.save');
        Route::post('tenants/{tenant}/telephony', [PlatformController::class, 'saveClientTelephony'])->name('tenants.telephony');
        Route::get('settings', [PlatformController::class, 'settings'])->name('settings');
        Route::post('settings', [PlatformController::class, 'saveSettings'])->name('settings.save');
    });
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        // Public routes
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('forgot-password', 'forgotPassword');
        Route::post('reset-password', 'resetPassword');
        Route::post('resend-otp', 'resendOtp');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('profile', 'getProfile');
            Route::put('update-profile', 'updateProfile');
            Route::post('change-password', 'changePassword');
        });
    });
});

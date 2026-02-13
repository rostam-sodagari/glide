<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthenticationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\VerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public auth routes
    Route::post('auth/login', [AuthenticationController::class, 'login'])->name('auth.login');
    Route::post('auth/register', [AuthenticationController::class, 'register'])->name('auth.register');
    Route::post('auth/forgot-password', [PasswordResetController::class, 'forgot'])->name('auth.forgot-password');
    Route::post('auth/reset-password', [PasswordResetController::class, 'reset'])->name('auth.reset-password');
    Route::get('auth/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify')->middleware('signed.api');
    Route::post('auth/resend-verification', [VerificationController::class, 'resend'])->name('verification.resend');
    Route::post('auth/logout', [AuthenticationController::class, 'logout'])->middleware('auth:sanctum')->name('auth.logout');
});

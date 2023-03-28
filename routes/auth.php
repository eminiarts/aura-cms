<?php

use Eminiarts\Aura\Http\Controllers\Auth\AuthenticatedSessionController;
use Eminiarts\Aura\Http\Controllers\Auth\ConfirmablePasswordController;
use Eminiarts\Aura\Http\Controllers\Auth\EmailVerificationNotificationController;
use Eminiarts\Aura\Http\Controllers\Auth\EmailVerificationPromptController;
use Eminiarts\Aura\Http\Controllers\Auth\InvitationRegisterUserController;
use Eminiarts\Aura\Http\Controllers\Auth\NewPasswordController;
use Eminiarts\Aura\Http\Controllers\Auth\PasswordController;
use Eminiarts\Aura\Http\Controllers\Auth\PasswordResetLinkController;
use Eminiarts\Aura\Http\Controllers\Auth\RegisteredUserController;
use Eminiarts\Aura\Http\Controllers\Auth\TeamInvitationController;
use Eminiarts\Aura\Http\Controllers\Auth\VerifyEmailController;
use Eminiarts\Aura\Http\Controllers\SwitchTeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->name('aura.')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])->name('register');

    Route::get('register/{team}/{teamInvitation}', [InvitationRegisterUserController::class, 'create'])->name('invitation.register')->middleware(['signed']);

    Route::post('register/{team}/{teamInvitation}', [InvitationRegisterUserController::class, 'store'])->middleware(['signed']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->name('aura.')->group(function () {
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::get('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // If has Team Features
    Route::put('/current-team', [SwitchTeamController::class, 'update'])->name('current-team.update');

    Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])->middleware(['signed'])->name('team-invitations.accept');
});

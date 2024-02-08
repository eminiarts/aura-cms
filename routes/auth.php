<?php

use Aura\Base\Http\Controllers\Auth\ConfirmablePasswordController;
use Aura\Base\Http\Controllers\Auth\EmailVerificationNotificationController;
use Aura\Base\Http\Controllers\Auth\EmailVerificationPromptController;
use Aura\Base\Http\Controllers\Auth\InvitationRegisterUserController;
use Aura\Base\Http\Controllers\Auth\NewPasswordController;
use Aura\Base\Http\Controllers\Auth\PasswordController;
use Aura\Base\Http\Controllers\Auth\PasswordResetLinkController;
use Aura\Base\Http\Controllers\Auth\RegisteredUserController;
use Aura\Base\Http\Controllers\Auth\TeamInvitationController;
use Aura\Base\Http\Controllers\Auth\VerifyEmailController;
use Aura\Base\Http\Controllers\SwitchTeamController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('logout', [AuthenticatedSessionController::class, 'destroy'])->name('aura.logout');

Route::middleware('guest')->name('aura.')->group(function () {

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])->name('register.post');

    Route::get('register/{team}/{teamInvitation}', [InvitationRegisterUserController::class, 'create'])->name('invitation.register')->middleware(['signed']);

    Route::post('register/{team}/{teamInvitation}', [InvitationRegisterUserController::class, 'store'])->middleware(['signed'])->name('invitation.register.post');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->name('aura.')->group(function () {
    Route::get('email/verify', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');

    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // If has Team Features
    Route::put('/current-team', [SwitchTeamController::class, 'update'])->name('current-team.update');

    Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])->middleware(['signed'])->name('team-invitations.accept');
});

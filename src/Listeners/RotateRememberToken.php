<?php

namespace Aura\Base\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\TwoFactorAuthenticationEvent;

/**
 * Confirming or turning off two-factor authentication rotates the user's
 * remember token, so "remember me" cookies issued before no longer log in
 * without the challenge (the session guard restores them without one).
 */
class RotateRememberToken
{
    public function handle(TwoFactorAuthenticationEvent $event): void
    {
        /** @var mixed $user Fortify documents App\Models\User, a class the package cannot know. */
        $user = $event->user;

        // Fortify's actions save the user, so it is always an Eloquent model.
        if (! $user instanceof Model || ! $user instanceof Authenticatable) {
            return;
        }

        // Same as EloquentUserProvider::updateRememberToken(): a security change, not an edit.
        $timestamps = $user->timestamps;
        $user->timestamps = false;
        $user->setRememberToken(Str::random(60));
        $user->save();
        $user->timestamps = $timestamps;
    }
}

<?php

namespace Aura\Base\Support;

use Aura\Base\Resources\User;
use Closure;

final class GlobalAdminEmails
{
    private static bool $trustedCreation = false;

    public static function contains(string $email): bool
    {
        return in_array(strtolower(trim($email)), array_map(
            fn (string $address): string => strtolower(trim($address)),
            config('aura.global_admin.emails', []),
        ), true);
    }

    public static function isTrustedCreation(): bool
    {
        return self::$trustedCreation;
    }

    /**
     * Only the administrator bootstrap command uses this scoped bypass.
     *
     * @param  Closure(): User  $callback
     */
    public static function trustedCreation(Closure $callback): User
    {
        $previous = self::$trustedCreation;
        self::$trustedCreation = true;

        try {
            return $callback();
        } finally {
            self::$trustedCreation = $previous;
        }
    }
}

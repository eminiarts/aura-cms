<?php

namespace Aura\Base\Policies;

use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use ReflectionMethod;

final class ResourcePolicySubject
{
    /**
     * Evaluate Aura's instance-oriented type policy without recursively
     * entering the Gate pipeline. Explicit host policies keep Laravel's native
     * class-string and trailing-context argument behavior.
     *
     * @param  array<int, mixed>  $arguments
     */
    public static function evaluate(
        ResourcePolicy|TeamPolicy $policy,
        Authenticatable $user,
        string $ability,
        Resource|string $subject,
        array $arguments,
    ): mixed {
        if (is_callable([$policy, 'before'])) {
            $result = $policy->before($user, $ability, ...$arguments);

            if ($result !== null) {
                return $result;
            }
        }

        $arguments[0] = self::normalize($subject);

        return $policy->{$ability}($user, ...$arguments);
    }

    /**
     * @param  class-string<\Aura\Base\Resource>|\Aura\Base\Resource  $subject
     */
    public static function normalize(Resource|string $subject): Resource
    {
        if ($subject instanceof Resource) {
            return $subject;
        }

        return new $subject;
    }

    /**
     * @param  class-string  $subject
     */
    public static function supports(string $ability, string $subject): bool
    {
        return in_array($ability, ['create', 'viewAny'], true)
            && is_a($subject, Resource::class, true);
    }

    public static function usesAuraSubject(ResourcePolicy|TeamPolicy $policy, string $ability): bool
    {
        return in_array(
            (new ReflectionMethod($policy, $ability))->getDeclaringClass()->getName(),
            [ResourcePolicy::class, TeamPolicy::class],
            true,
        );
    }
}

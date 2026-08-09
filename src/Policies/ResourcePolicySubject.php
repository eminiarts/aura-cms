<?php

namespace Aura\Base\Policies;

use Aura\Base\Resource;
use ReflectionMethod;

final class ResourcePolicySubject
{
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

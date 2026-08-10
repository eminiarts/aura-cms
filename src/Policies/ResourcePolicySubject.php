<?php

namespace Aura\Base\Policies;

use Aura\Base\Contracts\TableResource;

final class ResourcePolicySubject
{
    /**
     * @param  class-string<TableResource>|TableResource  $subject
     */
    public static function normalize(TableResource|string $subject): TableResource
    {
        if ($subject instanceof TableResource) {
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
            && is_a($subject, TableResource::class, true);
    }
}

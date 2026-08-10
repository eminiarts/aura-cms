<?php

namespace Aura\Base\Traits;

use Aura\Base\Database\Relations\TeamMembershipBelongsToMany;

trait HasTeamMemberships
{
    protected function teamMembershipsToMany(
        string $related,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $relation,
    ): TeamMembershipBelongsToMany {
        $instance = $this->newRelatedInstance($related);

        return new TeamMembershipBelongsToMany(
            $instance->newQuery(),
            $this,
            'user_role',
            $foreignPivotKey,
            $relatedPivotKey,
            $this->getKeyName(),
            $instance->getKeyName(),
            $relation,
        );
    }
}

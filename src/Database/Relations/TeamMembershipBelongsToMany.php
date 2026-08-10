<?php

namespace Aura\Base\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeamMembershipBelongsToMany extends BelongsToMany
{
    public function detach($ids = null, $touch = true): int
    {
        $query = $this->newPivotQuery();

        if ($ids !== null) {
            $ids = $this->parseIds($ids);
            $query->whereIn($this->getQualifiedRelatedPivotKeyName(), $ids);
        }

        $memberships = $query->get()->map(
            fn ($membership) => $this->newExistingPivot((array) $membership),
        );

        $deleted = $memberships->sum(
            fn ($membership): int => $membership->delete(),
        );

        if ($touch) {
            $this->touchIfTouching();
        }

        return $deleted;
    }
}

<?php

namespace Aura\Base\Table;

use Aura\Base\Contracts\DeclaresTableParentScopes;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final class TableParentScopeResolver
{
    /**
     * @param  array{scope: string, id: int|string}  $state
     */
    public function accepts(Resource $resource, array $state): bool
    {
        return $this->resolveDescriptor($resource, $state['scope']) !== null;
    }

    /**
     * @param  array{scope: string, id: int|string}  $state
     */
    public function apply(
        Builder $query,
        Resource $resource,
        array $state,
        ?Authenticatable $actor = null,
    ): Builder {
        $descriptor = $this->resolveDescriptor($resource, $state['scope']);

        if ($descriptor === null) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $actor ??= auth()->user();

        if ($actor === null) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $parentClass = $descriptor->parentResource;
        $resolveParent = fn (): Resource => $parentClass::query()->findOrFail($state['id']);

        if (config('aura.teams')) {
            if (! $actor instanceof User) {
                $query->whereRaw('1 = 0');

                return $query;
            }

            $teamId = $actor->currentTeamIdForAuthorization();

            if ($teamId === null) {
                $query->whereRaw('1 = 0');

                return $query;
            }

            $parent = TeamScope::forTeam($teamId, $resolveParent, $actor->getConnection());
        } else {
            $parent = $resolveParent();
        }

        Gate::forUser($actor)->authorize($descriptor->ability, $parent);

        return $query->where(
            $resource->qualifyColumn($descriptor->foreignKey),
            $parent->getKey(),
        );
    }

    private function resolveDescriptor(Resource $resource, string $key): ?TableParentScope
    {
        if (! $resource instanceof DeclaresTableParentScopes) {
            return null;
        }

        foreach ($resource->tableParentScopes() as $declaredKey => $descriptor) {
            if (! $descriptor instanceof TableParentScope) {
                throw new InvalidArgumentException('Invalid table parent scope declaration.');
            }

            if ($descriptor->key === $key || (is_string($declaredKey) && $declaredKey === $key)) {
                return $descriptor;
            }
        }

        return null;
    }
}

<?php

namespace Aura\Base\Table;

use Aura\Base\Contracts\DeclaresTableParentScopes;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final class TableParentScopeResolver
{
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
        $parentClass = $descriptor->parentResource;
        $parent = $parentClass::query()->findOrFail($state['id']);
        $actor ??= auth()->user();

        if ($actor === null) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        Gate::forUser($actor)->authorize($descriptor->ability, $parent);

        return $query->where(
            $resource->qualifyColumn($descriptor->foreignKey),
            $parent->getKey(),
        );
    }

    private function resolveDescriptor(Resource $resource, string $key): TableParentScope
    {
        if (! $resource instanceof DeclaresTableParentScopes) {
            throw new InvalidArgumentException('This resource does not declare table parent scopes.');
        }

        foreach ($resource->tableParentScopes() as $declaredKey => $descriptor) {
            if (! $descriptor instanceof TableParentScope) {
                throw new InvalidArgumentException('Invalid table parent scope declaration.');
            }

            if ($descriptor->key === $key || (is_string($declaredKey) && $declaredKey === $key)) {
                return $descriptor;
            }
        }

        throw new InvalidArgumentException('Unknown table parent scope.');
    }
}

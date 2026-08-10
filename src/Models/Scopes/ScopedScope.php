<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use WeakMap;

class ScopedScope implements Scope
{
    /**
     * Per-request cache of the resolved scope decision.
     *
     * Keyed by the authenticated user object instance so it resets naturally
     * between requests and between tests: when a user instance is garbage
     * collected the WeakMap entry disappears with it, and every test resolves
     * a fresh user instance, so no state can leak across tests.
     *
     * @var WeakMap<User, array<string, array{super: bool, scoped: bool}>>|null
     */
    protected static ?WeakMap $decisionCache = null;

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model instanceof Role) {
            return $builder;
        }
        if ($model instanceof User) {
            return $builder;
        }

        if ($model instanceof Resource && ! $model::usesOwnerScope()) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return $builder;
        }

        ['super' => $isSuperAdmin, 'scoped' => $isScoped] = $this->decisionFor($user, $model);

        // Superadmin
        if ($isSuperAdmin) {
            return $builder;
        }

        if ($isScoped) {
            $ownerColumn = $model instanceof Resource ? $model::getOwnerColumn() : 'user_id';

            if ($ownerColumn !== null) {
                $builder->where($model->qualifyColumn($ownerColumn), $user->getKey());
            }
        }

        // Check access?
        return $builder;
    }

    public static function flushState(): void
    {
        static::$decisionCache = null;
    }

    /**
     * Resolve (and memoize) the scope decision for the given user and model.
     *
     * @return array{super: bool, scoped: bool}
     */
    protected function decisionFor(User $user, Model $model): array
    {
        static::$decisionCache ??= new WeakMap;

        $userCache = static::$decisionCache[$user] ?? [];

        $key = $model->getMorphClass();

        if (! array_key_exists($key, $userCache)) {
            $userCache[$key] = [
                'super' => $user->isSuperAdmin(),
                'scoped' => $user->hasPermissionTo('scope', $model),
            ];

            static::$decisionCache[$user] = $userCache;
        }

        return $userCache[$key];
    }
}

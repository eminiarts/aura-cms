<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TeamScope implements Scope
{
    private static bool $applying = false;

    private static int $bypassDepth = 0;

    /** @var array<int|string, int|null> */
    private static array $currentTeamIds = [];

    /** @var list<int|string> */
    private static array $trustedTeamContexts = [];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! config('aura.teams') || self::$bypassDepth > 0) {
            return;
        }

        if (self::$applying) {
            return;
        }

        self::$applying = true;

        try {
            $currentTeamId = $this->getCurrentTeamId();
            $authUser = Auth::user();
            $hasTenantContext = self::$trustedTeamContexts !== [];

            if ($model->getTable() === 'users') {
                if ($hasTenantContext && $currentTeamId !== null) {
                    $builder->whereHas('teams', function ($query) use ($currentTeamId) {
                        $query->where('teams.id', $currentTeamId);
                    });

                    return;
                }

                $isGlobalAdmin = $authUser && Gate::forUser($authUser)->allows(User::GLOBAL_ADMIN_GATE);

                if ($isGlobalAdmin) {
                    return;
                }

                if ($currentTeamId !== null) {
                    $builder->whereHas('teams', function ($query) use ($currentTeamId) {
                        $query->where('teams.id', $currentTeamId);
                    });

                    return;
                }

                if ($authUser && ! $hasTenantContext) {
                    $builder->whereKey($authUser->getAuthIdentifier());

                    return;
                }

                $builder->whereRaw('1 = 0');

                return;
            }

            if ($model->getTable() === 'teams') {
                if ($hasTenantContext && $currentTeamId !== null) {
                    $builder->whereKey($currentTeamId);
                } elseif (! $authUser) {
                    $builder->whereRaw('1 = 0');
                }

                return;
            }

            $sharesRecordsAcrossTeams = $model instanceof Resource
                && $model::sharesRecordsAcrossTeams();

            if ($currentTeamId === null) {
                if ($authUser && $sharesRecordsAcrossTeams) {
                    $builder->whereNull($model->getTable().'.team_id');
                } else {
                    $builder->whereRaw('1 = 0');
                }

                return;
            }

            if ($sharesRecordsAcrossTeams) {
                $column = $model->getTable().'.team_id';

                $builder->where(function (Builder $query) use ($column, $currentTeamId) {
                    $query->where($column, $currentTeamId)->orWhereNull($column);
                });

                return;
            }

            $builder->where($model->getTable().'.team_id', $currentTeamId);
        } finally {
            self::$applying = false;
        }
    }

    public static function currentContextTeamId(): int|string|null
    {
        if (self::$trustedTeamContexts === []) {
            return null;
        }

        return self::$trustedTeamContexts[array_key_last(self::$trustedTeamContexts)];
    }

    public static function flushState(): void
    {
        self::$applying = false;
        self::$bypassDepth = 0;
        self::$currentTeamIds = [];
        self::$trustedTeamContexts = [];
    }

    public static function forgetCurrentTeamId(string|int|null $userId): void
    {
        if ($userId === null) {
            return;
        }

        unset(self::$currentTeamIds[$userId]);
    }

    /**
     * Execute a complete background query inside an explicit tenant context.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    public static function forTeam(int|string $teamId, callable $callback): mixed
    {
        self::$trustedTeamContexts[] = $teamId;

        try {
            return $callback();
        } finally {
            array_pop(self::$trustedTeamContexts);
        }
    }

    /**
     * Invalidate a user's request snapshot now, but preserve the last committed
     * shared-cache value until an open transaction actually commits.
     */
    public static function invalidateCurrentTeamId(string|int|null $userId): void
    {
        if ($userId === null) {
            return;
        }

        self::forgetCurrentTeamId($userId);

        $connection = DB::connection();

        if (! self::hasActiveApplicationTransaction($connection)) {
            Cache::forget(User::currentTeamCacheKey($userId));

            return;
        }

        $connection->afterCommit(function () use ($userId): void {
            self::forgetCurrentTeamId($userId);
            Cache::forget(User::currentTeamCacheKey($userId));
        });
    }

    /**
     * Execute a complete trusted query without tenant constraints.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    public static function withoutTenantScope(callable $callback): mixed
    {
        self::$bypassDepth++;

        try {
            return $callback();
        } finally {
            self::$bypassDepth--;
        }
    }

    /**
     * Get the current team ID without triggering the scope again.
     *
     * @return int|null
     */
    private function getCurrentTeamId()
    {
        if (self::$trustedTeamContexts !== []) {
            return self::$trustedTeamContexts[array_key_last(self::$trustedTeamContexts)];
        }

        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();

        $connection = DB::connection();

        if (self::hasActiveApplicationTransaction($connection)) {
            return $connection->table('users')->where('id', $userId)->value('current_team_id');
        }

        if (array_key_exists($userId, self::$currentTeamIds)) {
            return self::$currentTeamIds[$userId];
        }

        $cacheKey = User::currentTeamCacheKey($userId);

        if (Cache::has($cacheKey)) {
            $cachedTeamId = Cache::get($cacheKey);

            return self::$currentTeamIds[$userId] = $cachedTeamId === false ? null : $cachedTeamId;
        }

        // Direct database query to avoid triggering scopes.
        $currentTeamId = DB::table('users')->where('id', $userId)->value('current_team_id');

        Cache::forever($cacheKey, $currentTeamId ?? false);

        return self::$currentTeamIds[$userId] = $currentTeamId;
    }

    private static function hasActiveApplicationTransaction(Connection $connection): bool
    {
        $transactionLevel = $connection->transactionLevel();

        if (app()->runningUnitTests()) {
            // Laravel's database test traits keep one outer rollback-only
            // transaction open. Nested levels represent application work.
            return $transactionLevel > 1;
        }

        return $transactionLevel > 0;
    }
}

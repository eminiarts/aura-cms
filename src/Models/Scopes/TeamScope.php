<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resource;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\User;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseTransactionRecord;
use Illuminate\Database\DatabaseTransactionsManager;
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

    /** @var array<string, int|string|null> */
    private static array $currentTeamIds = [];

    /** @var list<array{connection: string, team_id: int|string}> */
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
            if (! $this->contextUsesModelConnection($model)) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $currentTeamId = $this->getCurrentTeamId($model);
            $authUser = Auth::user();
            $hasTenantContext = self::$trustedTeamContexts !== [];

            if (Option::isEveryoneTeamId($currentTeamId)) {
                $builder->whereRaw('1 = 0');

                return;
            }

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
                    $builder->whereKey($authUser->getKey());

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

    public static function currentContextTeamId(?Connection $connection = null): int|string|null
    {
        if (self::$trustedTeamContexts === []) {
            return null;
        }

        $context = self::$trustedTeamContexts[array_key_last(self::$trustedTeamContexts)];

        if ($connection !== null
            && $context['connection'] !== User::connectionCacheIdentity($connection)) {
            return null;
        }

        return $context['team_id'];
    }

    /**
     * Resolve an authenticated user's current team from the authoritative
     * connection while retaining this scope's request and epoch cache.
     */
    public static function currentTeamIdForUser(User $user): int|string|null
    {
        $userId = $user->getKey();

        if ($userId === null || $userId === '') {
            return null;
        }

        $connection = $user->getConnection();

        if (self::hasActiveApplicationTransaction($connection)) {
            return $connection->table($user->getTable())
                ->useWritePdo()
                ->where($user->getKeyName(), $userId)
                ->value('current_team_id');
        }

        $cacheKey = User::currentTeamCacheKey($userId, $connection);

        if (array_key_exists($cacheKey, self::$currentTeamIds)) {
            return self::$currentTeamIds[$cacheKey];
        }

        if (Cache::has($cacheKey)) {
            $cachedTeamId = Cache::get($cacheKey);

            return self::$currentTeamIds[$cacheKey] = $cachedTeamId === false ? null : $cachedTeamId;
        }

        $currentTeamId = $connection->table($user->getTable())
            ->useWritePdo()
            ->where($user->getKeyName(), $userId)
            ->value('current_team_id');

        Cache::put($cacheKey, $currentTeamId ?? false, now()->addHour());

        return self::$currentTeamIds[$cacheKey] = $currentTeamId;
    }

    public static function flushState(): void
    {
        self::$applying = false;
        self::$bypassDepth = 0;
        self::$currentTeamIds = [];
        self::$trustedTeamContexts = [];
    }

    public static function forgetCurrentTeamId(
        string|int|null $userId,
        ?Connection $connection = null,
    ): void {
        if ($userId === null || $userId === '') {
            return;
        }

        $connection ??= self::resolveConnection();

        unset(self::$currentTeamIds[User::currentTeamCacheKey($userId, $connection)]);
    }

    /**
     * Execute a complete background query inside an explicit tenant context.
     *
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    public static function forTeam(
        int|string $teamId,
        callable $callback,
        ?Connection $connection = null,
    ): mixed {
        $connection ??= self::resolveConnection();
        self::$trustedTeamContexts[] = [
            'connection' => User::connectionCacheIdentity($connection),
            'team_id' => $teamId,
        ];

        try {
            return $callback();
        } finally {
            array_pop(self::$trustedTeamContexts);
        }
    }

    public static function hasContextForConnection(Connection $connection): bool
    {
        if (self::$trustedTeamContexts === []) {
            return false;
        }

        $context = self::$trustedTeamContexts[array_key_last(self::$trustedTeamContexts)];

        return $context['connection'] === User::connectionCacheIdentity($connection);
    }

    /**
     * Invalidate a user's request snapshot now, but preserve the last committed
     * shared-cache value until an open transaction actually commits.
     */
    public static function invalidateCurrentTeamId(
        string|int|null $userId,
        ?Connection $connection = null,
    ): void {
        if ($userId === null || $userId === '') {
            return;
        }

        $connection ??= self::resolveConnection();
        $cacheKey = User::currentTeamCacheKey($userId, $connection);

        unset(self::$currentTeamIds[$cacheKey]);

        self::afterApplicationCommit($connection, function () use ($cacheKey, $connection, $userId): void {
            unset(self::$currentTeamIds[$cacheKey]);
            User::rotateCurrentTeamCacheEpoch($userId, $connection);
            Cache::forget($cacheKey);
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

    private static function afterApplicationCommit(Connection $connection, callable $callback): void
    {
        $transactionsManager = self::transactionsManager();

        if ($transactionsManager) {
            $transaction = self::applicationTransaction($transactionsManager, $connection);

            if ($transaction) {
                // Bind to this connection's outer application transaction. The
                // framework's generic afterCommit() callback attaches to the
                // most recently opened transaction across every connection,
                // which can publish tenant state at an unrelated boundary.
                $transaction->addCallback($callback);

                return;
            }

            $callback();

            return;
        }

        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit($callback);

            return;
        }

        $callback();
    }

    private static function applicationTransaction(
        DatabaseTransactionsManager $transactionsManager,
        Connection $connection,
    ): ?DatabaseTransactionRecord {
        return $transactionsManager
            ->callbackApplicableTransactions()
            ->first(fn (DatabaseTransactionRecord $transaction): bool => $transaction->connection === $connection->getName());
    }

    private function contextUsesModelConnection(Model $model): bool
    {
        $modelIdentity = User::connectionCacheIdentity($model->getConnection());

        if (self::$trustedTeamContexts !== []) {
            $context = self::$trustedTeamContexts[array_key_last(self::$trustedTeamContexts)];

            return $context['connection'] === $modelIdentity;
        }

        $authenticatedUser = Auth::user();

        return $authenticatedUser === null
            || ($authenticatedUser instanceof User
                && User::connectionCacheIdentity($authenticatedUser->getConnection()) === $modelIdentity);
    }

    /**
     * Get the current team ID without triggering the scope again.
     */
    private function getCurrentTeamId(Model $model): int|string|null
    {
        if (self::$trustedTeamContexts !== []) {
            return self::currentContextTeamId($model->getConnection());
        }

        $authenticatedUser = Auth::user();

        if (! $authenticatedUser) {
            return null;
        }

        if (! $authenticatedUser instanceof User) {
            return null;
        }

        return self::currentTeamIdForUser($authenticatedUser);
    }

    private static function hasActiveApplicationTransaction(Connection $connection): bool
    {
        $transactionsManager = self::transactionsManager();

        if ($transactionsManager) {
            return self::applicationTransaction($transactionsManager, $connection) !== null;
        }

        return $connection->transactionLevel() > 0;
    }

    private static function resolveConnection(): Connection
    {
        $authenticatedUser = Auth::user();

        if ($authenticatedUser instanceof Model) {
            return $authenticatedUser->getConnection();
        }

        return DB::connection();
    }

    private static function transactionsManager(): ?DatabaseTransactionsManager
    {
        if (! app()->bound('db.transactions')) {
            return null;
        }

        $transactionsManager = app('db.transactions');

        return $transactionsManager instanceof DatabaseTransactionsManager
            ? $transactionsManager
            : null;
    }
}

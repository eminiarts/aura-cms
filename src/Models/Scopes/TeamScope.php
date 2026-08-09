<?php

namespace Aura\Base\Models\Scopes;

use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TeamScope implements Scope
{
    // Static flag to prevent recursive calls
    private static $applying = false;

    /** @var array<int|string, int|null> */
    private static array $currentTeamIds = [];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (! config('aura.teams')) {
            return;
        }

        // Prevent recursive calls
        if (self::$applying) {
            return;
        }

        // Don't apply scope in console (optional, as you commented it out)
        // if (app()->runningInConsole()) {
        //     return;
        // }

        self::$applying = true;

        try {
            $currentTeamId = $this->getCurrentTeamId();
            // Handle User model specially
            if ($model->getTable() === 'users') {
                $authUser = Auth::user();
                $isGlobalAdmin = $authUser && Gate::forUser($authUser)->allows(User::GLOBAL_ADMIN_GATE);

                if (! $isGlobalAdmin) {
                    if ($currentTeamId !== null) {
                        $builder->whereHas('teams', function ($query) use ($currentTeamId) {
                            $query->where('teams.id', $currentTeamId);
                        });
                    } elseif ($authUser) {
                        $builder->whereKey($authUser->getAuthIdentifier());
                    }
                }

                self::$applying = false;

                return;  // Early return is important.
            }

            // For Team model, don't apply team scope
            if ($model->getTable() === 'teams') {
                self::$applying = false;

                return;
            }

            $sharesRecordsAcrossTeams = $model instanceof Resource
                && $model::sharesRecordsAcrossTeams();

            if ($currentTeamId === null) {
                if (Auth::check()) {
                    if ($sharesRecordsAcrossTeams) {
                        $builder->whereNull($model->getTable().'.team_id');
                    } else {
                        $builder->whereRaw('1 = 0');
                    }
                }

                self::$applying = false;

                return;
            }

            if ($sharesRecordsAcrossTeams) {
                $column = $model->getTable().'.team_id';

                $builder->where(function (Builder $query) use ($column, $currentTeamId) {
                    $query->where($column, $currentTeamId)->orWhereNull($column);
                });

                self::$applying = false;

                return;
            }

            // For all other models, filter by team_id
            $builder->where($model->getTable().'.team_id', $currentTeamId);

            self::$applying = false;

            return;

        } catch (\Exception $e) {
            self::$applying = false;
            throw $e;
        }
    }

    public static function flushState(): void
    {
        self::$applying = false;
        self::$currentTeamIds = [];
    }

    public static function forgetCurrentTeamId(string|int|null $userId): void
    {
        if ($userId === null) {
            return;
        }

        unset(self::$currentTeamIds[$userId]);
    }

    /**
     * Get the current team ID without triggering the scope again.
     *
     * @return int|null
     */
    private function getCurrentTeamId()
    {
        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();

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
}

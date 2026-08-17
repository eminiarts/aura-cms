<?php

namespace Aura\Base\Support;

use Aura\Base\Resources\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Installs an explicit Team and actor for a request or queued unit of work.
 *
 * The context never changes the user's persisted current_team_id. It is a
 * stack so nested operations restore their caller, and every mutation is
 * unwound in finally to remain safe in queue workers and long-lived runtimes.
 */
class TeamExecutionContext
{
    /** @var array<int, array{team_id: int, actor: mixed}> */
    private static array $stack = [];

    public static function active(): bool
    {
        return self::$stack !== [];
    }

    public static function actor(): mixed
    {
        return data_get(self::$stack, array_key_last(self::$stack).'.actor');
    }

    public static function clear(): void
    {
        self::$stack = [];
    }

    public static function currentTeamId(): ?int
    {
        $teamId = data_get(self::$stack, array_key_last(self::$stack).'.team_id');

        return is_numeric($teamId) ? (int) $teamId : null;
    }

    public function run(int|string|null $teamId, mixed $actor, Closure $callback): mixed
    {
        if (! config('aura.teams')) {
            return $callback();
        }

        if (! is_numeric($teamId) || (int) $teamId < 1) {
            throw new InvalidArgumentException('A valid Team is required for explicit Team execution.');
        }

        if (! $actor instanceof Model || ! $actor instanceof Authenticatable) {
            throw new AuthorizationException('A persisted Aura actor is required for explicit Team execution.');
        }

        $teamId = (int) $teamId;
        $actorId = $actor->getAuthIdentifier();

        if (! $actorId || ! DB::table('teams')->where('id', $teamId)->exists()) {
            throw new AuthorizationException('The Team execution context is unavailable.');
        }

        $globalAdmin = Gate::forUser($actor)->allows(User::GLOBAL_ADMIN_GATE);
        $member = DB::table('user_role')
            ->where('team_id', $teamId)
            ->where('user_id', $actorId)
            ->exists();

        if (! $globalAdmin && ! $member) {
            throw new AuthorizationException('The actor is no longer a member of this Team.');
        }

        $guard = Auth::guard();
        $previousActor = $guard->user();
        $previousTeamId = $actor->getAttribute('current_team_id');
        $hadCurrentTeamRelation = method_exists($actor, 'relationLoaded') && $actor->relationLoaded('currentTeam');
        $previousCurrentTeam = $hadCurrentTeamRelation ? $actor->getRelation('currentTeam') : null;

        self::$stack[] = ['team_id' => $teamId, 'actor' => $actor];
        $actor->setAttribute('current_team_id', $teamId);

        if (method_exists($actor, 'unsetRelation')) {
            $actor->unsetRelation('currentTeam');
        }

        $guard->setUser($actor);

        try {
            return $callback();
        } finally {
            array_pop(self::$stack);
            $actor->setAttribute('current_team_id', $previousTeamId);

            if ($hadCurrentTeamRelation && method_exists($actor, 'setRelation')) {
                $actor->setRelation('currentTeam', $previousCurrentTeam);
            } elseif (method_exists($actor, 'unsetRelation')) {
                $actor->unsetRelation('currentTeam');
            }

            if ($previousActor) {
                $guard->setUser($previousActor);
            } else {
                $guard->forgetUser();
            }
        }
    }
}

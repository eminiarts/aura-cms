<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Aura\Base\Services\InvitationConnectionResolver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationRegisterUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request, mixed $team, mixed $teamInvitation): View
    {
        // If team registration is disabled, we show a 404 page.
        abort_if(! config('aura.auth.user_invitations'), 404);

        [$team, $teamInvitation] = $this->resolveInvitation($request, $team, $teamInvitation);

        // An email that already has an account must accept the invitation, not
        // register a second one — refuse the register form outright (the mail
        // routes existing accounts to the accept link anyway).
        abort_if($this->emailAlreadyRegistered($teamInvitation->email, $team->getConnection()), 403);

        return view('aura::auth.user_invitation', [
            'team' => $team,
            'teamInvitation' => $teamInvitation,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     *
     * @throws ValidationException
     */
    public function store(Request $request, mixed $team, mixed $teamInvitation): RedirectResponse
    {
        abort_if(! config('aura.auth.user_invitations'), 404);

        $connection = $this->invitationConnection($request, $team, $teamInvitation);
        [$teamId, $invitationId] = $this->invitationRouteKeys($team, $teamInvitation);

        // Resolve and lock every authorization input on the writer inside one
        // transaction. A lagging replica can therefore never resurrect a revoked
        // invitation, stale meta or role, nor hide an already-created account.
        $user = $connection->transaction(function () use ($connection, $invitationId, $request, $teamId) {
            [$team, $teamInvitation] = $this->resolveInvitationFromWriter(
                $connection,
                $teamId,
                $invitationId,
                lockForUpdate: true,
            );

            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            /** @var Role $roleResource */
            $roleResource = app(config('aura.resources.role'));
            $roleResource = $roleResource->newInstance();
            $roleResource->setConnection($connection->getName());
            $role = $roleResource->newQueryWithoutScopes()
                ->useWritePdo()
                ->lockForUpdate()
                ->whereKey($teamInvitation->role)
                ->visibleToTeam($team->id)
                ->first();

            abort_unless($role, 404);
            abort_if($this->emailAlreadyRegistered(
                $teamInvitation->email,
                $connection,
                lockForUpdate: true,
            ), 403);

            /** @var User $userResource */
            $userResource = app(config('aura.resources.user'));
            $userResource = $userResource->newInstance();
            $userResource->setConnection($connection->getName());
            $user = $userResource->newQuery()->create([
                'name' => $request->name,
                'email' => $teamInvitation->email,
                'password' => $request->password,
                'current_team_id' => $team->id,
                'fields' => ['roles' => [$role->id]],
            ]);

            $teamInvitation->delete();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(config('aura.auth.redirect'));
    }

    /**
     * Whether an account already exists for the given email, compared
     * case-insensitively (consistent with the accept path's strcasecmp match).
     */
    protected function emailAlreadyRegistered(
        ?string $email,
        Connection $connection,
        bool $lockForUpdate = false,
    ): bool {
        if ($email === null || $email === '') {
            return false;
        }

        /** @var User $user */
        $user = app(config('aura.resources.user'));
        $user = $user->newInstance();
        $user->setConnection($connection->getName());

        $query = $user->newQueryWithoutScopes()
            ->without('meta')
            ->useWritePdo()
            ->whereRaw('lower(email) = ?', [mb_strtolower($email)]);

        if ($lockForUpdate) {
            return $query->lockForUpdate()->first([$user->getQualifiedKeyName()]) !== null;
        }

        return $query->exists();
    }

    protected function invitationConnection(
        Request $request,
        mixed $team,
        mixed $teamInvitation,
    ): Connection {
        $modelParameters = collect([$team, $teamInvitation])
            ->filter(fn (mixed $parameter): bool => $parameter instanceof Model)
            ->values();

        if ($modelParameters->isNotEmpty()) {
            $candidate = $modelParameters[0]->getConnection();
        } else {
            /** @var Team $configuredTeam */
            $configuredTeam = app(config('aura.resources.team'));
            $candidate = $configuredTeam->getConnection();
        }

        $connection = app(InvitationConnectionResolver::class)->resolve($request, $candidate);

        foreach ($modelParameters as $parameter) {
            abort_unless(
                User::connectionCacheIdentity($parameter->getConnection())
                    === User::connectionCacheIdentity($connection),
                404,
            );
        }

        return $connection;
    }

    /**
     * @return array{0: int|string, 1: int|string}
     */
    protected function invitationRouteKeys(mixed $team, mixed $teamInvitation): array
    {
        $teamId = $team instanceof Team ? $team->getRouteKey() : $team;
        $invitationId = $teamInvitation instanceof TeamInvitation
            ? $teamInvitation->getRouteKey()
            : $teamInvitation;

        abort_unless(is_int($teamId) || is_string($teamId), 404);
        abort_unless(is_int($invitationId) || is_string($invitationId), 404);

        return [$teamId, $invitationId];
    }

    /**
     * Signed invitation routes are a narrow, explicit guest lookup bypass.
     * Resolve both records unscoped, then bind the invitation back to the team
     * encoded in the same signed URL.
     *
     * @return array{0: Team, 1: TeamInvitation}
     */
    protected function resolveInvitation(
        Request $request,
        mixed $team,
        mixed $teamInvitation,
    ): array {
        $connection = $this->invitationConnection($request, $team, $teamInvitation);
        [$teamId, $invitationId] = $this->invitationRouteKeys($team, $teamInvitation);

        return $this->resolveInvitationFromWriter($connection, $teamId, $invitationId);
    }

    /**
     * @return array{0: Team, 1: TeamInvitation}
     */
    protected function resolveInvitationFromWriter(
        Connection $connection,
        int|string $teamId,
        int|string $invitationId,
        bool $lockForUpdate = false,
    ): array {

        /** @var Team $teamResource */
        $teamResource = app(config('aura.resources.team'));
        $teamResource = $teamResource->newInstance();
        $teamResource->setConnection($connection->getName());

        /** @var TeamInvitation $invitationResource */
        $invitationResource = app(config('aura.resources.team-invitation'));
        $invitationResource = $invitationResource->newInstance();
        $invitationResource->setConnection($connection->getName());

        $teamQuery = $teamResource->newQueryWithoutScopes()
            ->without('meta')
            ->useWritePdo();
        $invitationQuery = $invitationResource->newQueryWithoutScopes()
            ->without('meta')
            ->useWritePdo();

        if ($lockForUpdate) {
            $teamQuery->lockForUpdate();
            $invitationQuery->lockForUpdate();
        }

        $resolvedTeam = $teamQuery->findOrFail($teamId);
        $resolvedInvitation = $invitationQuery
            ->whereKey($invitationId)
            ->where('team_id', $resolvedTeam->getKey())
            ->firstOrFail();

        $teamMeta = $resolvedTeam->meta()->useWritePdo();
        $invitationMeta = $resolvedInvitation->meta()->useWritePdo();

        if ($lockForUpdate) {
            $teamMeta->lockForUpdate();
            $invitationMeta->lockForUpdate();
        }

        $resolvedTeam->setRelation('meta', $teamMeta->get());
        $resolvedInvitation->setRelation('meta', $invitationMeta->get());

        return [$resolvedTeam, $resolvedInvitation];
    }
}

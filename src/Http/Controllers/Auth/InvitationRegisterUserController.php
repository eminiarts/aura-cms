<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
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
     *
     * @return View
     */
    public function create(Request $request, mixed $team, mixed $teamInvitation)
    {
        // If team registration is disabled, we show a 404 page.
        abort_if(! config('aura.auth.user_invitations'), 404);

        [$team, $teamInvitation] = $this->resolveInvitation($team, $teamInvitation);

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
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request, mixed $team, mixed $teamInvitation)
    {
        abort_if(! config('aura.auth.user_invitations'), 404);

        [$team, $teamInvitation] = $this->resolveInvitation($team, $teamInvitation);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // The carried role must still exist and be assignable in the inviting team
        // — its own Team Role or a shared Global Role (team_id = null) — mirroring
        // the team-or-global rule TeamInvitationController::accept applies. A role
        // deleted between invite and acceptance fails like the accept path (404)
        // and, thanks to the transaction below, leaves no orphaned user behind.
        $connection = $team->getConnection();
        /** @var Role $roleResource */
        $roleResource = app(config('aura.resources.role'));
        $roleResource = $roleResource->newInstance();
        $roleResource->setConnection($connection->getName());
        $role = $roleResource->newQueryWithoutScopes()
            ->whereKey($teamInvitation->role)
            ->visibleToTeam($team->id)
            ->first();

        abort_unless($role, 404);

        // An email that already belongs to an account (any casing) must accept the
        // invitation, not register a second account. Refuse rather than mint a
        // case-variant duplicate.
        abort_if($this->emailAlreadyRegistered($teamInvitation->email, $connection), 403);

        // Create the user and consume the invitation atomically: a mid-flight
        // failure (e.g. the Roles field refusing the assignment) rolls the insert
        // back, so a refusal never leaves a half-provisioned, role-less account.
        $user = $connection->transaction(function () use ($connection, $request, $team, $teamInvitation, $role) {
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
    protected function emailAlreadyRegistered(?string $email, Connection $connection): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        /** @var User $user */
        $user = app(config('aura.resources.user'));
        $user = $user->newInstance();
        $user->setConnection($connection->getName());

        return $user->newQueryWithoutScopes()
            ->whereRaw('lower(email) = ?', [mb_strtolower($email)])
            ->exists();
    }

    protected function invitationConnection(mixed $team, mixed $teamInvitation): Connection
    {
        $modelParameters = collect([$team, $teamInvitation])
            ->filter(fn (mixed $parameter): bool => $parameter instanceof Model)
            ->values();

        if ($modelParameters->count() === 2) {
            abort_unless(
                User::connectionCacheIdentity($modelParameters[0]->getConnection())
                    === User::connectionCacheIdentity($modelParameters[1]->getConnection()),
                404,
            );
        }

        if ($modelParameters->isNotEmpty()) {
            return $modelParameters[0]->getConnection();
        }

        /** @var Team $configuredTeam */
        $configuredTeam = app(config('aura.resources.team'));

        return $configuredTeam->getConnection();
    }

    /**
     * Signed invitation routes are a narrow, explicit guest lookup bypass.
     * Resolve both records unscoped, then bind the invitation back to the team
     * encoded in the same signed URL.
     *
     * @return array{0: Team, 1: TeamInvitation}
     */
    protected function resolveInvitation(mixed $team, mixed $teamInvitation): array
    {
        $connection = $this->invitationConnection($team, $teamInvitation);
        $teamId = $team instanceof Team ? $team->getRouteKey() : $team;
        $invitationId = $teamInvitation instanceof TeamInvitation
            ? $teamInvitation->getRouteKey()
            : $teamInvitation;

        /** @var Team $teamResource */
        $teamResource = app(config('aura.resources.team'));
        $teamResource = $teamResource->newInstance();
        $teamResource->setConnection($connection->getName());

        /** @var TeamInvitation $invitationResource */
        $invitationResource = app(config('aura.resources.team-invitation'));
        $invitationResource = $invitationResource->newInstance();
        $invitationResource->setConnection($connection->getName());

        $resolvedTeam = $teamResource->newQueryWithoutScopes()->findOrFail($teamId);
        $resolvedInvitation = $invitationResource->newQueryWithoutScopes()
            ->whereKey($invitationId)
            ->where('team_id', $resolvedTeam->getKey())
            ->firstOrFail();

        return [$resolvedTeam, $resolvedInvitation];
    }
}

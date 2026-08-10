<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Mail\TeamInvitation as TeamInvitationMail;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Aura\Base\Services\InvitationConnectionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class TeamInvitationController extends Controller
{
    /**
     * Accept a team invitation.
     */
    public function accept(Request $request, string|int $invitation): RedirectResponse
    {
        abort_unless(config('aura.teams'), 404);

        $authenticatedUser = $request->user();
        abort_unless($authenticatedUser instanceof User, 403);

        $connection = app(InvitationConnectionResolver::class)
            ->resolve($request, $authenticatedUser->getConnection());
        abort_unless(
            User::connectionCacheIdentity($authenticatedUser->getConnection())
                === User::connectionCacheIdentity($connection),
            404,
        );
        /** @var TeamInvitation $invitationResource */
        $invitationResource = app(config('aura.resources.team-invitation'));
        $invitationResource = $invitationResource->newInstance();
        $invitationResource->setConnection($connection->getName());
        $invitation = $invitationResource->newQueryWithoutScopes()
            ->without('meta')
            ->useWritePdo()
            ->findOrFail($invitation);
        $invitation = $this->loadInvitationMetaFromWriter($invitation);

        /** @var Team $teamResource */
        $teamResource = app(config('aura.resources.team'));
        $teamResource = $teamResource->newInstance();
        $teamResource->setConnection($connection->getName());
        $team = $teamResource->newQueryWithoutScopes()
            ->useWritePdo()
            ->findOrFail($invitation->team_id);
        $userId = $authenticatedUser->getAuthIdentifier();

        abort_unless(is_int($userId) || is_string($userId), 403);

        /** @var User $userResource */
        $userResource = app(config('aura.resources.user'));
        $userResource = $userResource->newInstance();
        $userResource->setConnection($connection->getName());
        $user = $userResource->newQueryWithoutScopes()
            ->useWritePdo()
            ->where($authenticatedUser->getAuthIdentifierName(), $userId)
            ->firstOrFail();
        $userEmail = $user->getAttribute('email');

        abort_unless(is_string($userEmail) && strcasecmp($userEmail, $invitation->email) === 0, 403);

        if (! $user->teams()->useWritePdo()->whereKey($team->id)->exists()) {
            // The invitation may carry a Team Role owned by this team or a shared
            // Global Role (team_id = null). Accept either, but still refuse a role
            // owned by a different team so invitations cannot inject cross-team
            // access. The Membership records the team via the pivot regardless.
            /** @var Role $roleResource */
            $roleResource = app(config('aura.resources.role'));
            $roleResource = $roleResource->newInstance();
            $roleResource->setConnection($connection->getName());
            $role = $roleResource->newQueryWithoutScopes()
                ->useWritePdo()
                ->whereKey($invitation->role)
                ->visibleToTeam($team->id)
                ->firstOrFail();

            $user->roles()->attach($role->id, ['team_id' => $team->id]);
            Cache::forget(User::teamListCacheKey($user->getKey(), $user->getConnection()));
            $user->unsetRelation('teams');
        }

        $user->switchTeam($team);

        $invitation->delete();

        return redirect()
            ->route('aura.dashboard')
            ->with('status', __('Great! You have accepted the invitation to join the :team team.', ['team' => $team->getAttribute('name')]));
    }

    /**
     * Cancel the given team invitation.
     */
    public function destroy(Request $request, Team $team, string|int $invitation): RedirectResponse
    {
        abort_unless(config('aura.teams'), 404);
        $this->ensureTeamUsesRequestConnection($request, $team);

        $invitation = $this->invitationForTeam($team, $invitation);

        $invitation->delete();

        return back(303)->with('status', __('Team invitation revoked.'));
    }

    /**
     * Resend the given team invitation.
     */
    public function resend(Request $request, Team $team, string|int $invitation): RedirectResponse
    {
        abort_unless(config('aura.teams'), 404);
        $this->ensureTeamUsesRequestConnection($request, $team);

        $invitation = $this->invitationForTeam($team, $invitation);

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));

        return back(303)->with('status', __('Team invitation resent.'));
    }

    protected function ensureTeamUsesRequestConnection(Request $request, Team $team): void
    {
        $authenticatedUser = $request->user();

        abort_unless(
            $authenticatedUser instanceof User
                && User::connectionCacheIdentity($authenticatedUser->getConnection())
                    === User::connectionCacheIdentity($team->getConnection()),
            404,
        );
    }

    protected function invitationForTeam(Team $team, string|int $invitation): TeamInvitation
    {
        /** @var TeamInvitation $invitationResource */
        $invitationResource = app(config('aura.resources.team-invitation'));
        $invitationResource = $invitationResource->newInstance();
        $invitationResource->setConnection($team->getConnectionName());

        $invitation = $invitationResource->newQueryWithoutScopes()
            ->without('meta')
            ->useWritePdo()
            ->whereKey($invitation)
            ->where('team_id', $team->id)
            ->firstOrFail();

        return $this->loadInvitationMetaFromWriter($invitation);
    }

    protected function loadInvitationMetaFromWriter(TeamInvitation $invitation): TeamInvitation
    {
        $invitation->setRelation('meta', $invitation->meta()->useWritePdo()->get());

        return $invitation;
    }
}

<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Mail\TeamInvitation as TeamInvitationMail;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Model;
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
        $connectionName = $authenticatedUser instanceof Model
            ? $authenticatedUser->getConnectionName()
            : (new User)->getConnectionName();
        $invitation = TeamInvitation::on($connectionName)->withoutGlobalScopes()->findOrFail($invitation);
        $team = Team::on($connectionName)->withoutGlobalScopes()->findOrFail($invitation->team_id);
        $userId = $authenticatedUser->getAuthIdentifier();

        abort_unless(is_int($userId) || is_string($userId), 403);

        $user = User::on($connectionName)->withoutGlobalScopes()->whereKey($userId)->firstOrFail();
        $userEmail = $user->getAttribute('email');

        abort_unless(is_string($userEmail) && strcasecmp($userEmail, $invitation->email) === 0, 403);

        if (! $user->teams()->whereKey($team->id)->exists()) {
            // The invitation may carry a Team Role owned by this team or a shared
            // Global Role (team_id = null). Accept either, but still refuse a role
            // owned by a different team so invitations cannot inject cross-team
            // access. The Membership records the team via the pivot regardless.
            $role = Role::on($connectionName)
                ->withoutGlobalScopes()
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

        $invitation = $this->invitationForTeam($team, $invitation);

        Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));

        return back(303)->with('status', __('Team invitation resent.'));
    }

    protected function invitationForTeam(Team $team, string|int $invitation): TeamInvitation
    {
        return TeamInvitation::on($team->getConnectionName())
            ->withoutGlobalScopes()
            ->whereKey($invitation)
            ->where('team_id', $team->id)
            ->firstOrFail();
    }
}

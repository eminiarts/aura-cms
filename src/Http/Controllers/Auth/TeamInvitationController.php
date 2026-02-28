<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Resources\TeamInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class TeamInvitationController extends Controller
{
    /**
     * Accept a team invitation.
     *
     * @param  int  $invitationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(Request $request, $invitationId)
    {
        $invitation = TeamInvitation::whereKey($invitationId)->firstOrFail();

        $user = $request->user();

        abort_unless($user && $user->email === $invitation->email, 403, 'This invitation is not for you.');

        $team = $invitation->team;

        // Attach user to the team with the correct role
        $user->roles()->syncWithPivotValues([$invitation->role], ['team_id' => $team->id]);

        // Set the user's current team
        $user->update(['current_team_id' => $team->id]);

        $invitation->delete();

        return redirect(config('aura.auth.redirect'))->with('success',
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $team->name]),
        );
    }

    /**
     * Cancel the given team invitation.
     *
     * @param  int  $invitationId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $invitationId)
    {
        $invitation = TeamInvitation::whereKey($invitationId)->firstOrFail();

        if (! Gate::forUser($request->user())->check('removeTeamMember', $invitation->team)) {
            throw new AuthorizationException;
        }

        $invitation->delete();

        return back(303);
    }
}

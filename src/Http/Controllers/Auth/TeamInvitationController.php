<?php

namespace Eminiarts\Aura\Http\Controllers\Auth;

use Eminiarts\Aura\Resources\TeamInvitation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Laravel\Jetstream\Jetstream;

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
        $model = new TeamInvitation();

        $invitation = $model::whereKey($invitationId)->firstOrFail();

        app(AddsTeamMembers::class)->add(
            $invitation->team->owner,
            $invitation->team,
            $invitation->email,
            $invitation->role
        );

        $invitation->delete();

        return redirect(config('fortify.home'))->banner(
            __('Great! You have accepted the invitation to join the :team team.', ['team' => $invitation->team->name]),
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
        $model = Jetstream::teamInvitationModel();

        $invitation = $model::whereKey($invitationId)->firstOrFail();

        if (! Gate::forUser($request->user())->check('removeTeamMember', $invitation->team)) {
            throw new AuthorizationException();
        }

        $invitation->delete();

        return back(303);
    }
}
<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Resources\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchTeamController extends Controller
{
    /**
     * Update the authenticated user's current team.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $authenticatedUser = $request->user();
        abort_unless($authenticatedUser instanceof Model, 403);

        /** @var Team $teamResource */
        $teamResource = app(config('aura.resources.team'));
        $teamResource = $teamResource->newInstance();
        $teamResource->setConnection($authenticatedUser->getConnectionName());
        $team = $teamResource->newQuery()->findOrFail($request->team_id);

        if (! $authenticatedUser->switchTeam($team)) {
            abort(403);
        }

        return redirect(route('aura.dashboard'), 303);
    }
}

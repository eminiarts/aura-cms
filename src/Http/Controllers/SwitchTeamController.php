<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Resources\Team;
use Illuminate\Http\Request;

class SwitchTeamController extends Controller
{
    /**
     * Update the authenticated user's current team.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $team = Team::findOrFail($request->team_id);

        if (! $request->user()->switchTeam($team)) {
            abort(403);
        }

        return redirect(route('aura.dashboard'), 303);
    }
}

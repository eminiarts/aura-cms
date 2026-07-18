<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function create(Request $request, Team $team, TeamInvitation $teamInvitation)
    {
        // If team registration is disabled, we show a 404 page.
        abort_if(! config('aura.auth.user_invitations'), 404);

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
    public function store(Request $request, Team $team, TeamInvitation $teamInvitation)
    {
        abort_if(! config('aura.auth.user_invitations'), 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // The carried role must still exist and be assignable in the inviting team
        // — its own Team Role or a shared Global Role (team_id = null) — mirroring
        // the team-or-global rule TeamInvitationController::accept applies. A role
        // deleted between invite and acceptance fails like the accept path (404)
        // and, thanks to the transaction below, leaves no orphaned user behind.
        $role = Role::withoutGlobalScopes()
            ->whereKey($teamInvitation->role)
            ->visibleToTeam($team->id)
            ->first();

        abort_unless($role, 404);

        // An email that already belongs to an account (any casing) must accept the
        // invitation, not register a second account. Refuse rather than mint a
        // case-variant duplicate.
        abort_if(
            User::withoutGlobalScopes()
                ->whereRaw('lower(email) = ?', [mb_strtolower((string) $teamInvitation->email)])
                ->exists(),
            403
        );

        // Create the user and consume the invitation atomically: a mid-flight
        // failure (e.g. the Roles field refusing the assignment) rolls the insert
        // back, so a refusal never leaves a half-provisioned, role-less account.
        $user = DB::transaction(function () use ($request, $team, $teamInvitation, $role) {
            $user = User::create([
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
}

<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Registered;
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

        $user = User::create([
            'name' => $request->name,
            'email' => $teamInvitation->email,
            'password' => $request->password,
            'current_team_id' => $team->id,
            'fields' => ['roles' => [$teamInvitation->role]],
        ]);

        // Delete the invitation
        $teamInvitation->delete();

        event(new Registered($user));

        Auth::login($user);

        return redirect(config('aura.auth.redirect'));
    }
}

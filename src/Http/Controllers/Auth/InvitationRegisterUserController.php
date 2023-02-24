<?php

namespace Eminiarts\Aura\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Facades\Aura;
use Illuminate\Validation\Rules;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Eminiarts\Aura\Resources\TeamInvitation;
use Eminiarts\Aura\Http\Controllers\Controller;
use Eminiarts\Aura\Providers\RouteServiceProvider;

class InvitationRegisterUserController extends Controller
{
    /**
    * Display the registration view.
    *
    * @return \Illuminate\View\View
    */
    public function create(Request $request, Team $team, TeamInvitation $teamInvitation)
    {
        // If team registration is disabled, we show a 404 page.
        abort_if(!Aura::option('user_invitations'), 404);

        return view('aura::auth.user_invitation', [
            'team' => $team,
            'teamInvitation' => $teamInvitation,
        ]);
    }

    /**
    * Handle an incoming registration request.
    *
    * @return \Illuminate\Http\RedirectResponse
    *
    * @throws \Illuminate\Validation\ValidationException
    */
    public function store(Request $request, Team $team, TeamInvitation $teamInvitation)
    {
        abort_if(!Aura::option('user_invitations'), 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $teamInvitation->email,
            'password' => Hash::make($request->password),
            'current_team_id' => $team->id,
            'fields' => ['roles' => [$teamInvitation->role]]
        ]);

        // Delete the invitation
        $teamInvitation->delete();

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}

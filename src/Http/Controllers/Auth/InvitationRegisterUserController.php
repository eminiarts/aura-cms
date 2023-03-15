<?php

namespace Eminiarts\Aura\Http\Controllers\Auth;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Http\Controllers\Controller;
use Eminiarts\Aura\Providers\RouteServiceProvider;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\TeamInvitation;
use Eminiarts\Aura\Resources\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

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
        abort_if(! Aura::option('user_invitations'), 404);

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
        abort_if(! Aura::option('user_invitations'), 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $teamInvitation->email,
            'password' => Hash::make($request->password),
            'current_team_id' => $team->id,
            'fields' => ['roles' => [$teamInvitation->role]],
        ]);

        // dd($user->fresh()->toArray());

        // Delete the invitation
        $teamInvitation->delete();

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}

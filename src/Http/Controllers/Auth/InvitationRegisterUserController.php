<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Facades\Aura;
use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Providers\RouteServiceProvider;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
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

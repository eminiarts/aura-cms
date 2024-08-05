<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Facades\Aura;
use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // If team registration is disabled, we show a 404 page.
        abort_if(! config('aura.auth.registration'), 404);

        return view('aura::auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        abort_if(! config('aura.auth.registration'), 404);

        if (config('aura.teams')) {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'team' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $team = Team::create([
                'name' => $request->team,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user->current_team_id = $team->id;

            $user->save();

            $role = $team->roles->first();

            $user->update(['roles' => [$role->id]]);
        } else {
            // no aura.teams
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // ray('user created', $user);

            $role = Role::where('slug', 'user')->firstOrFail();

            $user->update(['roles' => [$role->id]]);
        }

        event(new Registered($user));

        Auth::login($user);
        // ray('user logged in');

        return redirect(config('aura.auth.redirect'));
    }
}

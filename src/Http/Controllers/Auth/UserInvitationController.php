<?php

namespace Aura\Base\Http\Controllers\Auth;

use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Resources\UserInvitation;
use Aura\Base\Rules\CaseInsensitiveUniqueEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserInvitationController extends Controller
{
    public function create(UserInvitation $invitation): View
    {
        $this->ensureEnabled();

        abort_if(app(config('aura.resources.user'))->newQueryWithoutScopes()
            ->whereRaw('lower(email) = ?', [mb_strtolower($invitation->email)])->exists(), 403);

        return view('aura::auth.user_invitation', ['team' => null, 'teamInvitation' => $invitation]);
    }

    public function store(Request $request, UserInvitation $invitation): RedirectResponse
    {
        $this->ensureEnabled();

        $user = DB::transaction(function () use ($request, $invitation): User {
            // Lock before consumption so the same invitation cannot register twice.
            $invitation = UserInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            $role = Role::whereKey($invitation->role)->firstOrFail();

            $validated = Validator::make([
                ...$request->only('name', 'password', 'password_confirmation'),
                'email' => $invitation->email,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', new CaseInsensitiveUniqueEmail],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ])->validate();

            $user = app(config('aura.resources.user'))::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'fields' => ['roles' => [$role->id]],
            ]);

            $invitation->delete();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect(config('aura.auth.redirect'));
    }

    protected function ensureEnabled(): void
    {
        abort_if(config('aura.teams') || ! config('aura.auth.user_invitations'), 404);
    }
}

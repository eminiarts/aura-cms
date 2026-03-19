<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\post;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Aura Settings', function () {
    it('settings page is accessible', function () {
        $this->get(route('aura.settings'))->assertOk();
    });

    it('settings page contains configuration options', function () {
        $response = $this->get(route('aura.settings'));

        $response->assertOk();
    });
});

describe('Team Registration with User', function () {
    it('can register new user with new team', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);
        config(['aura.auth.redirect' => '/admin']);

        Auth::logout();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'New Team',
        ];

        $this->withoutExceptionHandling();
        $response = post(route('aura.register.post'), $userData);

        $response->assertRedirect('/admin');

        $user = User::where('email', 'john@example.com')->first();
        expect($user)->not()->toBeNull();
        expect($user->name)->toBe('John Doe');

        $team = Team::where('name', 'New Team')->first();
        expect($team)->not()->toBeNull();

        expect($user->current_team_id)->toBe($team->id);
        expect($user->belongsToTeam($team))->toBeTrue();
    });

    it('sets the new user as team owner', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);
        config(['aura.auth.redirect' => '/admin']);

        Auth::logout();

        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Jane Team',
        ];

        $this->withoutExceptionHandling();
        post(route('aura.register.post'), $userData);

        $user = User::where('email', 'jane@example.com')->first();
        $team = Team::where('name', 'Jane Team')->first();

        expect($team->user_id)->toBe($user->id);
        expect($user->ownsTeam($team))->toBeTrue();
    });

    it('creates super_admin role for new team during registration', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);
        config(['aura.auth.redirect' => '/admin']);

        Auth::logout();

        $userData = [
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Bob Team',
        ];

        $this->withoutExceptionHandling();
        post(route('aura.register.post'), $userData);

        $team = Team::where('name', 'Bob Team')->first();

        $superAdminRole = Role::withoutGlobalScopes()
            ->where('team_id', $team->id)
            ->where('slug', 'super_admin')
            ->first();

        expect($superAdminRole)->not->toBeNull();
        expect($superAdminRole->super_admin)->toBeTrue();
    });

    it('assigns new user super_admin role for their team', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);
        config(['aura.auth.redirect' => '/admin']);

        Auth::logout();

        $userData = [
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Alice Team',
        ];

        $this->withoutExceptionHandling();
        post(route('aura.register.post'), $userData);

        $user = User::where('email', 'alice@example.com')->first();

        expect($user->isSuperAdmin())->toBeTrue();
    });

    it('logs in user after registration', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);
        config(['aura.auth.redirect' => '/admin']);

        Auth::logout();
        $this->assertGuest();

        $userData = [
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Charlie Team',
        ];

        $this->withoutExceptionHandling();
        post(route('aura.register.post'), $userData);

        $this->assertAuthenticated();
        expect(auth()->user()->email)->toBe('charlie@example.com');
    });
});

describe('Team Registration Validation', function () {
    it('requires team name for registration', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);

        Auth::logout();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => '',
        ];

        $response = post(route('aura.register.post'), $userData);

        $response->assertSessionHasErrors('team');
    });

    it('requires valid email for registration', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);

        Auth::logout();

        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Test Team',
        ];

        $response = post(route('aura.register.post'), $userData);

        $response->assertSessionHasErrors('email');
    });

    it('requires matching password confirmation', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);

        Auth::logout();

        $userData = [
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
            'team' => 'Test Team',
        ];

        $response = post(route('aura.register.post'), $userData);

        $response->assertSessionHasErrors('password');
    });

    it('prevents duplicate email registration', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);

        Auth::logout();

        // First registration
        $userData = [
            'name' => 'First User',
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'First Team',
        ];

        $this->withoutExceptionHandling();
        post(route('aura.register.post'), $userData);

        Auth::logout();
        $this->withExceptionHandling();

        // Second registration with same email
        $duplicateData = [
            'name' => 'Second User',
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Second Team',
        ];

        $response = post(route('aura.register.post'), $duplicateData);

        $response->assertSessionHasErrors('email');
    });
});

describe('Registration Configuration', function () {
    it('redirects to configured path after registration', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.teams' => true]);
        config(['aura.auth.redirect' => '/custom-dashboard']);

        Auth::logout();

        $userData = [
            'name' => 'Redirect User',
            'email' => 'redirect@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Redirect Team',
        ];

        $this->withoutExceptionHandling();
        $response = post(route('aura.register.post'), $userData);

        $response->assertRedirect('/custom-dashboard');
    });
});

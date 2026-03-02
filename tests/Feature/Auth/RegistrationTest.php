<?php

namespace Tests\Feature\Auth;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['aura.auth.registration' => true]);
    config(['aura.teams' => true]);
});

describe('Registration Screen', function () {
    test('registration page renders successfully', function () {
        $this->get(route('aura.register'))
            ->assertSuccessful()
            ->assertSee('Team')
            ->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('Password');
    });

    test('registration page returns 404 when disabled', function () {
        config(['aura.auth.registration' => false]);

        $this->get(route('aura.register'))
            ->assertNotFound();
    });

    test('register link is visible on login page when enabled', function () {
        $this->get(route('aura.login'))
            ->assertSee('Register.');
    });

    test('register link is hidden on login page when disabled', function () {
        config(['aura.auth.registration' => false]);

        $this->get(route('aura.login'))
            ->assertDontSee('Register.');
    });

    test('team input is shown when teams feature is enabled', function () {
        $this->get(route('aura.register'))
            ->assertSee('Team');
    });

    test('team input is hidden when teams feature is disabled', function () {
        config(['aura.teams' => false]);

        $this->get(route('aura.register'))
            ->assertDontSee('Team');
    });
});

describe('Registration With Teams', function () {
    beforeEach(function () {
        config(['aura.teams' => true]);
    });

    test('user can register with team', function () {
        Team::factory()->create();

        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Test Team',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(config('aura.auth.redirect'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('teams', ['name' => 'Test Team']);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'name' => 'Test User']);

        $team = Team::where('name', 'Test Team')->first();
        $user = auth()->user();

        expect($user->current_team_id)->toBe($team->id);
    });

    test('team role is created on registration', function () {
        Team::factory()->create();

        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'New Team',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $team = Team::where('name', 'New Team')->first();

        $this->assertDatabaseHas('roles', ['team_id' => $team->id]);
    });

    test('registered event is dispatched', function () {
        Event::fake([Registered::class]);
        Team::factory()->create();

        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Event Team',
            'email' => 'event@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Event::assertDispatched(Registered::class);
    });

    test('name is required', function () {
        $this->post(route('aura.register'), [
            'team' => 'Test Team',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('name');
    });

    test('team is required', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('team');
    });

    test('email is required', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Test Team',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('email must be valid format', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Test Team',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('email must be unique', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Test Team',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('password is required', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Test Team',
            'email' => 'test@example.com',
        ])
            ->assertSessionHasErrors('password');
    });

    test('password must be confirmed', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Test Team',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])
            ->assertSessionHasErrors('password');
    });

    test('all fields are required', function () {
        $this->post(route('aura.register'), [])
            ->assertSessionHasErrors(['name', 'team', 'email', 'password']);
    });
});

// Note: "Registration Without Teams" tests should be run with:
// vendor/bin/pest -c phpunit-without-teams.xml
// They are skipped here because the database schema still requires team_id in user_role table
describe('Registration Without Teams', function () {
    beforeEach(function () {
        config(['aura.teams' => false]);
    });

    test('team input is hidden when teams feature is disabled', function () {
        $this->get(route('aura.register'))
            ->assertDontSee('Team');
    });

    test('validation does not require team field', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionDoesntHaveErrors('team');
    });

    test('name is required', function () {
        $this->post(route('aura.register'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('name');
    });

    test('email is required', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('email must be valid format', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('email');
    });

    test('password must be confirmed', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])
            ->assertSessionHasErrors('password');
    });

    test('all fields are required except team', function () {
        $this->post(route('aura.register'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    });
});

describe('Registration Disabled', function () {
    beforeEach(function () {
        config(['aura.auth.registration' => false]);
    });

    test('get request returns 404', function () {
        $this->get(route('aura.register'))
            ->assertNotFound();
    });

    test('post request returns 404', function () {
        $this->post(route('aura.register'), [
            'name' => 'Test User',
            'team' => 'Test Team',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertNotFound();
    });
});

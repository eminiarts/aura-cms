<?php

use Aura\Base\Fields\GlobalAdmin;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\post;
use function Pest\Livewire\livewire;

/**
 * Promote a freshly minted user to Global Admin the trusted way (direct,
 * pipeline-bypassing write), the same posture the CLI bootstrap uses. Kept in
 * the test so the flag is never granted through a user-facing write path.
 */
function makeGlobalAdmin(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill(['global_admin' => true])->saveQuietly();

    return $user->refresh();
}

/**
 * A user who is a member of the given team (Membership pivot + current team), so
 * the team-scoped user form can find and edit them.
 */
function teamMember(Team $team): User
{
    $role = Role::where('team_id', $team->id)->first()
        ?? Role::factory()->create(['team_id' => $team->id]);

    $target = User::factory()->create();
    $target->roles()->attach($role->id, ['team_id' => $team->id]);
    $target->update(['current_team_id' => $team->id]);

    return $target->refresh();
}

describe('the global_admin field on the user form', function () {
    it('exposes the GlobalAdmin field type, on forms and out of the index', function () {
        $field = collect((new User)->getFields())->firstWhere('slug', 'global_admin');

        expect($field)->not->toBeNull()
            ->and($field['type'])->toBe(GlobalAdmin::class)
            ->and($field['on_forms'])->toBeTrue()
            ->and($field['on_index'])->toBeFalse();
    });
});

describe('the package AuraGlobalAdmin gate', function () {
    it('allows a user whose global_admin flag is true', function () {
        $ga = makeGlobalAdmin();

        expect(Gate::forUser($ga)->allows('AuraGlobalAdmin'))->toBeTrue();
    });

    it('denies a user whose global_admin flag is false', function () {
        $plain = User::factory()->create();

        expect(Gate::forUser($plain)->allows('AuraGlobalAdmin'))->toBeFalse();
    });

    it('reflects the flag through the acting-user entry point', function () {
        $ga = makeGlobalAdmin();
        $plain = User::factory()->create();

        $this->actingAs($ga);
        expect($ga->isAuraGlobalAdmin())->toBeTrue();

        $this->actingAs($plain);
        expect($plain->isAuraGlobalAdmin())->toBeFalse();
    });

    it('denies a guest', function () {
        auth()->logout();

        expect(Gate::allows('AuraGlobalAdmin'))->toBeFalse();
    });
});

describe('host override of the gate', function () {
    it('lets a host redefinition win over the package default (deny a flagged user)', function () {
        $ga = makeGlobalAdmin();

        // The package default would allow this flagged user; a later host
        // Gate::define replaces it (app providers boot after package providers).
        Gate::define('AuraGlobalAdmin', fn ($user) => false);

        expect(Gate::forUser($ga)->allows('AuraGlobalAdmin'))->toBeFalse();
    });

    it('lets a host redefinition win over the package default (allow an unflagged user)', function () {
        $plain = User::factory()->create();

        Gate::define('AuraGlobalAdmin', fn ($user) => true);

        expect(Gate::forUser($plain)->allows('AuraGlobalAdmin'))->toBeTrue();
    });
});

describe('TeamPolicy bypasses become live for Global Admins', function () {
    beforeEach(function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('TeamPolicy exists only with teams enabled.');
        }

        $this->policy = new TeamPolicy;
        // A team owned by a distinct user, so the acting users neither own nor
        // belong to it — any allow comes purely from the Global Admin bypass.
        $this->team = Team::factory()->createQuietly([
            'user_id' => User::factory()->create()->id,
        ]);
    });

    it('allows a Global Admin across the key team behaviors', function () {
        $ga = makeGlobalAdmin();
        $this->actingAs($ga);

        expect($this->policy->viewAny($ga, $this->team))->toBeTrue();
        expect($this->policy->create($ga, $this->team))->toBeTrue();
        expect($this->policy->update($ga, $this->team))->toBeTrue();
        expect($this->policy->delete($ga, $this->team))->toBeTrue();
        expect($this->policy->inviteUsers($ga, $this->team))->toBeTrue();
        expect($this->policy->addTeamMember($ga, $this->team))->toBeTrue();
    });

    it('refuses a non-member non-super-admin with the flag off', function () {
        $plain = User::factory()->create();
        $this->actingAs($plain);

        expect($this->policy->viewAny($plain, $this->team))->toBeFalse();
        expect($this->policy->create($plain, $this->team))->toBeFalse();
        expect($this->policy->update($plain, $this->team))->toBeFalse();
        expect($this->policy->delete($plain, $this->team))->toBeFalse();
        expect($this->policy->inviteUsers($plain, $this->team))->toBeFalse();
    });
});

describe('impersonation authorization keys off the flag', function () {
    it('lets a Global Admin impersonate', function () {
        $ga = makeGlobalAdmin();
        $this->actingAs($ga);

        expect($ga->canImpersonate())->toBeTrue();
    });

    it('does not let a non-Global-Admin impersonate', function () {
        $plain = User::factory()->create();
        $this->actingAs($plain);

        expect($plain->canImpersonate())->toBeFalse();
    });

    it('protects a Global Admin from being impersonated', function () {
        $ga = makeGlobalAdmin();
        $this->actingAs($ga);

        expect($ga->canBeImpersonated())->toBeFalse();
    });
});

describe('the flag cannot be self-granted (escalation guards)', function () {
    it('ignores global_admin passed to mass assignment', function () {
        $this->actingAs(createSuperAdmin()); // a Super Admin, but NOT a Global Admin

        $user = User::create([
            'name' => 'Escalation Attempt',
            'email' => 'escalation@example.com',
            'global_admin' => 1,
            'fields' => [
                'password' => 'Password123!XX',
            ],
        ]);

        expect($user->fresh()->global_admin)->toBeFalse();
    });

    it('ignores global_admin injected through the fields payload', function () {
        $this->actingAs(createSuperAdmin());

        $user = User::create([
            'name' => 'Fields Escalation',
            'email' => 'fields-escalation@example.com',
            'fields' => [
                'password' => 'Password123!XX',
                'global_admin' => 1,
            ],
        ]);

        expect($user->fresh()->global_admin)->toBeFalse();
    });

    it('ignores a guest attempting mass assignment', function () {
        auth()->logout();

        $user = User::create([
            'name' => 'Guest Escalation',
            'email' => 'guest-escalation@example.com',
            'global_admin' => 1,
            'fields' => [
                'password' => 'Password123!XX',
            ],
        ]);

        expect($user->fresh()->global_admin)->toBeFalse();
    });

    it('refuses a non-Global-Admin toggling the flag through the user form', function () {
        $actor = createSuperAdmin(); // Super Admin of the team, not a Global Admin
        $this->actingAs($actor);

        $target = teamMember($actor->currentTeam);

        livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
            ->set('form.fields.global_admin', true)
            ->call('save')
            ->assertHasNoErrors();

        expect($target->fresh()->global_admin)->toBeFalse();
    })->skip(fn () => ! config('aura.teams'), 'Uses the team-scoped user form.');

    it('never produces a Global Admin through registration, even with an injected payload', function () {
        config(['aura.auth.registration' => true]);
        config(['aura.auth.redirect' => '/admin']);
        auth()->logout();

        post(route('aura.register.post'), [
            'name' => 'Registrant',
            'team' => 'Registrant Team',
            'email' => 'registrant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'global_admin' => 1,
        ])->assertRedirect('/admin');

        $user = User::where('email', 'registrant@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->global_admin)->toBeFalse();
    })->skip(fn () => ! config('aura.teams'), 'Team registration flow requires teams enabled.');

    it('never produces a Global Admin through invitation registration', function () {
        config(['aura.auth.user_invitations' => true]);
        config(['aura.auth.redirect' => '/admin']);

        $team = Team::first() ?? Team::factory()->create();
        $role = globalAdminRole();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => $role->id,
        ]);

        $url = URL::signedRoute('aura.invitation.register', [$team, $invitation]);
        auth()->logout();

        $this->post($url, [
            'name' => 'Invited User',
            'password' => 'password',
            'password_confirmation' => 'password',
            'global_admin' => 1,
        ])->assertRedirect(config('aura.auth.redirect'));

        $user = User::where('email', 'invited@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->global_admin)->toBeFalse();
    })->skip(fn () => ! config('aura.teams'), 'Invitations are teams-on only.');
});

describe('a Global Admin can grant and revoke the flag', function () {
    it('lets a Global Admin toggle another user\'s flag through the form', function () {
        $ga = createSuperAdmin();
        $ga->forceFill(['global_admin' => true])->saveQuietly();
        $this->actingAs($ga->refresh());

        $target = teamMember($ga->currentTeam);

        // Grant.
        livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
            ->set('form.fields.global_admin', true)
            ->call('save')
            ->assertHasNoErrors();

        expect($target->fresh()->global_admin)->toBeTrue();

        // Revoke.
        livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
            ->set('form.fields.global_admin', false)
            ->call('save')
            ->assertHasNoErrors();

        expect($target->fresh()->global_admin)->toBeFalse();
    })->skip(fn () => ! config('aura.teams'), 'Uses the team-scoped user form.');
});

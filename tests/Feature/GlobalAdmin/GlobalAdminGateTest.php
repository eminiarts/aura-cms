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
use Lab404\Impersonate\Services\ImpersonateManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\post;
use function Pest\Livewire\livewire;

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
        $ga = createGlobalAdmin();

        expect(Gate::forUser($ga)->allows('AuraGlobalAdmin'))->toBeTrue();
    });

    it('denies a user whose global_admin flag is false', function () {
        $plain = User::factory()->create();

        expect(Gate::forUser($plain)->allows('AuraGlobalAdmin'))->toBeFalse();
    });

    it('reflects the flag through the acting-user entry point', function () {
        $ga = createGlobalAdmin();
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
        $ga = createGlobalAdmin();

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
        $ga = createGlobalAdmin();
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
    it('evaluates canImpersonate/canBeImpersonated on the right user, not the actor', function () {
        $ga = createGlobalAdmin();
        $plain = User::factory()->create();

        // No actingAs() here on purpose: the checks are instance-correct, so the
        // authenticated user must not leak into the answer (the auth-coupling bug).
        expect($ga->canImpersonate())->toBeTrue()
            ->and($plain->canImpersonate())->toBeFalse()
            ->and($ga->canBeImpersonated())->toBeFalse()
            ->and($plain->canBeImpersonated())->toBeTrue();
    });

    it('lets a Global Admin impersonate a plain member end-to-end through the row action', function () {
        $ga = createGlobalAdmin();
        $member = User::factory()->create();
        $this->actingAs($ga);

        // The row action is dispatched on the target user; the acting GA becomes
        // the impersonator.
        $member->impersonateAction();

        expect(app(ImpersonateManager::class)->isImpersonating())->toBeTrue()
            ->and(auth()->id())->toBe($member->id);
    });

    it('refuses a non-Global-Admin actor', function () {
        $actor = User::factory()->create(); // flag off
        $target = User::factory()->create();
        $this->actingAs($actor);

        expect(fn () => $target->impersonateAction())->toThrow(HttpException::class);
        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
    });

    it('protects a Global Admin from being impersonated, even by another Global Admin', function () {
        $ga = createGlobalAdmin();
        $otherGa = createGlobalAdmin();
        $this->actingAs($ga);

        expect(fn () => $otherGa->impersonateAction())->toThrow(HttpException::class);
        expect(app(ImpersonateManager::class)->isImpersonating())->toBeFalse();
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

    it('never grants Global Admin when an existing user accepts an invitation with the flag injected', function () {
        config(['aura.auth.user_invitations' => true]);

        $team = Team::first() ?? Team::factory()->create();
        $role = globalAdminRole();

        // An existing account whose email matches the invitation.
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'existing@example.com',
            'role' => $role->id,
        ]);

        // Sign the accept URL WITH the injected flag so the signature stays valid
        // and the tampered value actually reaches the controller.
        $url = URL::signedRoute('aura.team-invitations.accept', [
            'invitation' => $invitation->id,
            'global_admin' => 1,
        ]);

        $this->actingAs($existing)
            ->get($url)
            ->assertRedirect(route('aura.dashboard'));

        $existing->refresh();

        // The accept path ran (membership attached) but the flag was ignored.
        expect($existing->teams()->whereKey($team->id)->exists())->toBeTrue()
            ->and($existing->global_admin)->toBeFalse();
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

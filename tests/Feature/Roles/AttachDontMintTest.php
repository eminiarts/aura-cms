<?php

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\post;
use function Pest\Livewire\livewire;

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Attach-don\'t-mint behaviour is teams-on only.');
    }

    $this->actingAs($this->user = createSuperAdmin());
});

describe('Team creation attaches to the global admin role', function () {
    it('does not mint a per-team admin role and attaches the creator to the global role', function () {
        $team = Team::create(['name' => 'Fresh Team', 'user_id' => $this->user->id]);

        // No per-team admin row minted.
        expect(Role::withoutGlobalScopes()->where('team_id', $team->id)->exists())->toBeFalse();

        // The single global admin row backs the Membership.
        $globalAdmin = Role::withoutGlobalScopes()->whereNull('team_id')->where('slug', 'admin')->first();
        expect($globalAdmin)->not->toBeNull();

        $this->assertDatabaseHas('user_role', [
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role_id' => $globalAdmin->id,
        ]);
    });
});

describe('Registrant becomes Super Admin of their new team via the global role', function () {
    it('resolves to Super Admin with all permissions granted', function () {
        config(['aura.auth.registration' => true, 'aura.teams' => true, 'aura.auth.redirect' => '/admin']);
        Auth::logout();

        post(route('aura.register.post'), [
            'name' => 'Nadia New',
            'email' => 'nadia@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'team' => 'Nadia Team',
        ])->assertRedirect('/admin');

        $user = User::where('email', 'nadia@example.com')->first();
        $user->refresh();

        // Resolution seam: the Membership resolves through the global admin role.
        expect($user->isSuperAdmin())->toBeTrue();
        expect($user->hasRole('admin'))->toBeTrue();

        // Super Admin grants every permission via super_admin, not an enumerated set.
        expect($user->hasPermissionTo('viewAny', new User))->toBeTrue();
        expect($user->hasPermissionTo('delete', new Role))->toBeTrue();
    });
});

describe('Roles field team-ownership and escalation guards', function () {
    it('accepts a global role id assigned within the current team', function () {
        $globalAdmin = Role::withoutGlobalScopes()->whereNull('team_id')->where('slug', 'admin')->first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Global Grant')
            ->set('form.fields.email', 'global-grant@example.com')
            ->set('form.fields.password', 'Str0ng!Pass#2024')
            ->set('form.fields.roles', [$globalAdmin->id])
            ->call('save')
            ->assertHasNoErrors();

        $target = User::withoutGlobalScope(TeamScope::class)->where('email', 'global-grant@example.com')->first();

        $this->assertDatabaseHas('user_role', [
            'team_id' => $this->user->current_team_id,
            'user_id' => $target->id,
            'role_id' => $globalAdmin->id,
        ]);
    });

    it('refuses assigning a role owned by another team', function () {
        // A Team Role owned by a different team must never be assignable here.
        $otherTeam = Team::factory()->createQuietly();
        $otherTeamRole = Role::create([
            'name' => 'Other Editor',
            'slug' => 'other-editor',
            'team_id' => $otherTeam->id,
            'super_admin' => false,
            'permissions' => [],
        ]);

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Cross Team')
            ->set('form.fields.email', 'cross-team@example.com')
            ->set('form.fields.password', 'Str0ng!Pass#2024')
            ->set('form.fields.roles', [$otherTeamRole->id])
            ->call('save')
            ->assertStatus(403);

        $this->assertDatabaseMissing('user_role', [
            'role_id' => $otherTeamRole->id,
        ]);
    });

    it('refuses a non-super-admin assigning a super admin global role (escalation)', function () {
        $globalAdmin = Role::withoutGlobalScopes()->whereNull('team_id')->where('slug', 'admin')->first();

        // A delegated, non-super-admin manager acting in the team.
        $managerRole = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'team_id' => $this->user->current_team_id,
            'super_admin' => false,
            'permissions' => ['viewAny-user' => true, 'view-user' => true, 'update-user' => true],
        ]);

        $manager = User::factory()->create(['current_team_id' => $this->user->current_team_id]);
        $manager->roles()->attach($managerRole->id, ['team_id' => $this->user->current_team_id]);

        $target = User::factory()->create(['current_team_id' => $this->user->current_team_id]);
        $target->roles()->attach($managerRole->id, ['team_id' => $this->user->current_team_id]);

        $this->actingAs($manager);

        livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
            ->set('form.fields.roles', [$globalAdmin->id])
            ->call('save')
            ->assertStatus(403);

        $this->assertDatabaseMissing('user_role', [
            'user_id' => $target->id,
            'role_id' => $globalAdmin->id,
        ]);
    });
});

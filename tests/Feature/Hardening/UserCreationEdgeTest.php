<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

/**
 * Direct user-creation hardening (issue #54, user stories 11-13, 37).
 *
 * CreateUserTest already covers the happy path and the password-less edit keeping
 * the hash. These complement: the password-less edit still yields a WORKING login
 * (story 13 at the auth seam), the escalation guard on the user form, and the
 * cross-team duplicate-email outcome (a global users table with a unique email).
 */
beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('User-creation tests require the teams schema (teams-on only).');
    }

    $this->actingAs($this->user = createSuperAdmin());
});

it('keeps the password working after a password-less edit (login still succeeds)', function () {
    $target = User::factory()->create([
        'name' => 'Login Later',
        'email' => 'login-later@example.com',
        'password' => Hash::make('KnownPass123!'),
    ]);
    $target->teams()->attach($this->user->currentTeam->id, ['role_id' => Role::first()->id]);
    $target->update(['current_team_id' => $this->user->currentTeam->id]);

    Aura::fake();
    Aura::setModel($target);

    livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
        ->set('form.fields.name', 'Renamed')
        ->set('form.fields.email', 'renamed@example.com')
        ->call('save')
        ->assertHasNoErrors();

    // Story 13: the account is fully usable — the untouched password still logs in.
    auth()->logout();
    $this->post(route('aura.login'), [
        'email' => 'renamed@example.com',
        'password' => 'KnownPass123!',
    ])->assertRedirect(config('aura.auth.redirect'));

    $this->assertAuthenticated();
    expect(auth()->id())->toBe($target->id);
});

it('refuses a non-super-admin assigning a Super Admin role through the user form (escalation guard)', function () {
    $team = $this->user->currentTeam;
    $superRole = Role::first(); // seeded team admin role (super_admin = true)

    // A limited editor in the same team: may update users, is NOT a super admin.
    $editorRole = Role::create([
        'team_id' => $team->id, 'slug' => 'editor', 'type' => 'Role', 'title' => 'Editor',
        'name' => 'Editor', 'super_admin' => false,
        'permissions' => ['view-user' => true, 'viewAny-user' => true, 'update-user' => true],
    ]);
    $editor = User::factory()->create(['current_team_id' => $team->id]);
    $editor->roles()->syncWithPivotValues([$editorRole->id], ['team_id' => $team->id]);
    $editor->refresh();

    $target = User::factory()->create(['current_team_id' => $team->id]);
    $target->roles()->syncWithPivotValues([$editorRole->id], ['team_id' => $team->id]);

    Aura::fake();
    Aura::setModel($target);

    $this->actingAs($editor);

    livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
        ->set('form.fields.roles', [$superRole->id])
        ->call('save')
        ->assertStatus(403);

    // Escalation refused: the target never receives the Super Admin role.
    expect(
        DB::table('user_role')
            ->where('user_id', $target->id)
            ->where('role_id', $superRole->id)
            ->exists()
    )->toBeFalse();
    expect($target->fresh()->isSuperAdmin())->toBeFalse();
});

it('rejects creating a user whose email exists in another team with a validation error', function () {
    // Users are global rows keyed by a unique email. A user already exists in a
    // different team.
    $otherTeam = Team::factory()->create();
    User::factory()->create(['email' => 'crossteam@example.com', 'current_team_id' => $otherTeam->id]);

    $role = Role::first();

    // A clean field validation error, not a raw DB unique-constraint 500.
    livewire(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Cross Team')
        ->set('form.fields.email', 'crossteam@example.com')
        ->set('form.fields.current_team_id', $this->user->currentTeam->id)
        ->set('form.fields.password', 'Password123!XX')
        ->set('form.fields.password_confirmation', 'Password123!XX')
        ->set('form.fields.roles', [$role->id])
        ->call('save')
        ->assertHasErrors(['form.fields.email']);

    // No duplicate row was persisted.
    expect(User::withoutGlobalScopes()->where('email', 'crossteam@example.com')->count())->toBe(1);
});

it('rejects creating a user whose email differs only by case (validation error, no duplicate)', function () {
    User::factory()->create(['email' => 'existing-case@example.com', 'current_team_id' => $this->user->currentTeam->id]);

    $role = Role::first();

    livewire(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Case Dupe')
        ->set('form.fields.email', 'Existing-Case@Example.com')
        ->set('form.fields.current_team_id', $this->user->currentTeam->id)
        ->set('form.fields.password', 'Password123!XX')
        ->set('form.fields.password_confirmation', 'Password123!XX')
        ->set('form.fields.roles', [$role->id])
        ->call('save')
        ->assertHasErrors(['form.fields.email']);

    expect(User::withoutGlobalScopes()->whereRaw('lower(email) = ?', ['existing-case@example.com'])->count())->toBe(1);
});

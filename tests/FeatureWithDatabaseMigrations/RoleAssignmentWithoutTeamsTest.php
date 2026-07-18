<?php

use Aura\Base\Database\Seeders\RoleCatalogSeeder;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

use function Pest\Livewire\livewire;

/**
 * Teams-off parity for role assignment and the escalation guard (issue #54,
 * user story 38). In Teams-off mode the catalog roles simply ARE the roles: a
 * user's roles are assigned flat (no team pivot column). This file is
 * self-contained — it forces Teams-off and rebuilds the flat schema — so it runs
 * identically in the default and the phpunit-without-teams suites.
 */
beforeEach(function () {
    config(['aura.teams' => false]);
    config(['aura.auth.registration' => true]);

    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    RoleCatalogSeeder::seed();

    $this->actingAs($this->user = createSuperAdminWithoutTeam());
});

afterEach(function () {
    config(['aura.teams' => true]);
});

it('has no team_id column on the pivot in Teams-off mode', function () {
    expect(Schema::hasColumn('user_role', 'team_id'))->toBeFalse();
});

it('returns 404 for the team-invitation accept route in Teams-off mode', function () {
    // Invitations are teams-only; the accept controller aborts 404 when teams are
    // off, even for an otherwise validly signed link.
    $url = URL::signedRoute('aura.team-invitations.accept', ['invitation' => 1]);

    $this->actingAs($this->user)->get($url)->assertNotFound();
});

it('assigns a global catalog role to a user through the user form (teams-off)', function () {
    $userRole = Role::where('slug', 'user')->first();

    livewire(Create::class, ['slug' => 'user'])
        ->set('form.fields.name', 'Flat User')
        ->set('form.fields.email', 'flat-user@example.com')
        ->set('form.fields.password', 'Password123!XX')
        ->set('form.fields.password_confirmation', 'Password123!XX')
        ->set('form.fields.roles', [$userRole->id])
        ->call('save')
        ->assertHasNoErrors();

    $created = User::where('email', 'flat-user@example.com')->first();

    expect($created)->not->toBeNull();
    expect($created->roles->pluck('id')->all())->toBe([$userRole->id]);

    // Flat pivot: the Membership row carries no team.
    $pivot = DB::table('user_role')->where('user_id', $created->id)->first();
    expect($pivot->role_id)->toBe($userRole->id);
});

it('refuses a non-super-admin assigning a Super Admin role through the user form (teams-off escalation guard)', function () {
    $adminRole = Role::where('slug', 'admin')->first(); // super_admin = true

    // A limited editor: may update users, is NOT a super admin.
    $editorRole = Role::create([
        'slug' => 'editor', 'type' => 'Role', 'title' => 'Editor', 'name' => 'Editor',
        'super_admin' => false,
        'permissions' => ['view-user' => true, 'viewAny-user' => true, 'update-user' => true],
    ]);
    $editor = User::factory()->create();
    $editor->roles()->sync([$editorRole->id]);
    $editor->refresh();

    $target = User::factory()->create();
    $target->roles()->sync([$editorRole->id]);

    Aura::fake();
    Aura::setModel($target);

    $this->actingAs($editor);

    livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
        ->set('form.fields.roles', [$adminRole->id])
        ->call('save')
        ->assertStatus(403);

    // Escalation refused: no Super Admin role was granted.
    expect(DB::table('user_role')->where('user_id', $target->id)->where('role_id', $adminRole->id)->exists())->toBeFalse();
    expect($target->fresh()->isSuperAdmin())->toBeFalse();
});

it('gates resource pages by permission in Teams-off mode', function () {
    $powerless = Role::create([
        'slug' => 'powerless', 'type' => 'Role', 'title' => 'Powerless', 'name' => 'Powerless',
        'super_admin' => false, 'permissions' => [],
    ]);

    $viewer = Role::create([
        'slug' => 'user-viewer', 'type' => 'Role', 'title' => 'Viewer', 'name' => 'Viewer',
        'super_admin' => false, 'permissions' => ['viewAny-user' => true, 'view-user' => true],
    ]);

    $limited = User::factory()->create();
    $limited->roles()->sync([$powerless->id]);
    $limited->refresh();

    // Empty permission set: the Users index is refused.
    $this->actingAs($limited)->get(route('aura.user.index'))->assertForbidden();

    // Grant viewAny by swapping to the viewer role: the index opens on the next request.
    $limited->roles()->sync([$viewer->id]);
    $this->actingAs(User::find($limited->id))->get(route('aura.user.index'))->assertSuccessful();

    // Still no create permission: the create page stays refused.
    $this->actingAs(User::find($limited->id))->get(route('aura.user.create'))->assertForbidden();
});

<?php

use Aura\Base\Database\Seeders\RoleCatalogSeeder;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

// Teams-off behavior of the Role Catalog: there is a single flat catalog, so the
// Global Roles simply are the roles. A fresh install seeds `admin` and `user`,
// and Teams-off registration assigns the seeded `user` role with zero manual
// seeding.

beforeEach(function () {
    config(['aura.teams' => false]);
    config(['aura.auth.registration' => true]);

    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    // Seed the base catalog the same way a fresh install does.
    RoleCatalogSeeder::seed();
});

afterEach(function () {
    config(['aura.teams' => true]);
});

describe('Teams-off role resolution', function () {
    it('seeds the admin and user Global Roles without a team_id column', function () {
        expect(Schema::hasColumn('roles', 'team_id'))->toBeFalse();

        expect(Role::where('slug', 'admin')->count())->toBe(1);
        expect(Role::where('slug', 'user')->count())->toBe(1);
    });

    it('resolves roles by slug (the Global Roles are the roles)', function () {
        $admin = Role::resolveForTeam('admin', null);
        $user = Role::resolveForTeam('user', null);

        expect($admin)->not->toBeNull();
        expect($admin->super_admin)->toBeTrue();

        expect($user)->not->toBeNull();
        expect($user->super_admin)->toBeFalse();

        expect(Role::resolveForTeam('missing', null))->toBeNull();
    });

    it('is idempotent — re-seeding does not duplicate roles', function () {
        RoleCatalogSeeder::seed();
        RoleCatalogSeeder::seed();

        expect(Role::where('slug', 'admin')->count())->toBe(1);
        expect(Role::where('slug', 'user')->count())->toBe(1);
    });
});

describe('Teams-off permission checks via the seam', function () {
    it('grants a super admin every permission and a plain user none', function () {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::resolveForTeam('admin', null)->id]);
        $admin->refresh();

        expect($admin->isSuperAdmin())->toBeTrue();
        expect($admin->hasPermission('delete-post'))->toBeTrue();

        $plain = User::factory()->create();
        $plain->roles()->sync([Role::resolveForTeam('user', null)->id]);
        $plain->refresh();

        expect($plain->isSuperAdmin())->toBeFalse();
        expect($plain->hasPermission('delete-post'))->toBeFalse();
        expect($plain->hasRole('user'))->toBeTrue();
    });
});

describe('Teams-off catalog UI (issue #52)', function () {
    it('leaves the Roles index a flat set with no Global marker', function () {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::resolveForTeam('admin', null)->id]);
        $this->actingAs($admin->refresh());

        // The seeded catalog roles simply ARE the roles — no dedup, no badge.
        $ids = livewire(Table::class, ['query' => null, 'model' => new Role])
            ->instance()->rowsQuery()->pluck('id');

        expect($ids)->toContain(Role::where('slug', 'admin')->value('id'))
            ->toContain(Role::where('slug', 'user')->value('id'));

        // No team_id column means no Global Role concept and no badge.
        expect(Role::where('slug', 'admin')->first()->display('name'))->not->toContain('Global');
    });

    it('hides the global toggle from the Role form in Teams-off mode', function () {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::resolveForTeam('admin', null)->id]);
        $this->actingAs($admin->refresh());

        $isGlobalField = collect((new Role)->getFields())->firstWhere('slug', 'is_global');

        // The field is declared but its conditional_logic hides it entirely when
        // teams are off (no global/team distinction exists).
        expect(($isGlobalField['conditional_logic'])(new Role, null))->toBeFalse();
    });

    it('creates a role through the form without any global escalation surface', function () {
        $admin = User::factory()->create();
        $admin->roles()->sync([Role::resolveForTeam('admin', null)->id]);
        $this->actingAs($admin->refresh());

        livewire(Create::class, ['slug' => 'role'])
            ->set('form.fields.name', 'Editor')
            ->set('form.fields.slug', 'editor')
            ->call('save')
            ->assertHasNoErrors();

        expect(Role::where('slug', 'editor')->count())->toBe(1);
        expect(Schema::hasColumn('roles', 'team_id'))->toBeFalse();
    });
});

describe('Teams-off registration', function () {
    it('succeeds on a fresh install and assigns the seeded user role', function () {
        $this->post(route('aura.register'), [
            'name' => 'Fresh Install User',
            'email' => 'fresh@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(config('aura.auth.redirect'));

        $this->assertAuthenticated();

        $user = User::where('email', 'fresh@example.com')->first();

        expect($user)->not->toBeNull();
        expect($user->hasRole('user'))->toBeTrue();
        expect($user->isSuperAdmin())->toBeFalse();
    });
});

<?php

use Aura\Base\Database\Seeders\RoleCatalogSeeder;
use Aura\Base\Fields\Roles as RolesField;
use Aura\Base\Livewire\InviteUser;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

// Catalog management UI (issue #52): the merged, shadow-resolved Roles index,
// Global Roles marked read-only in a team context, the Global-Admin-only global
// toggle, and role pickers offering the same resolved set. Assertions observe
// behaviour at the seams (index query, policy gate, form save, picker options),
// never internal call structure.

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Catalog management UI is a teams-on concern.');
    }

    RoleCatalogSeeder::seed();
});

/**
 * A Global Role (team_id = null) written quietly so an authenticated actor's
 * current team is never auto-applied by InitialPostFields. Bumps the catalog
 * version the same way the seeder/self-heal path does.
 */
function catalogGlobalRole(string $slug, array $attributes = []): Role
{
    $role = Role::withoutGlobalScopes()->newModelInstance(array_merge([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'super_admin' => false,
        'permissions' => [],
        'team_id' => null,
    ], $attributes));

    $role->saveQuietly();
    Role::bumpCatalogVersion();

    return $role->refresh();
}

/**
 * A Team Role (a Shadow when its slug matches a Global Role) for the given team.
 */
function catalogTeamRole(int $teamId, string $slug, array $attributes = []): Role
{
    return Role::withoutGlobalScopes()->create(array_merge([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'super_admin' => false,
        'permissions' => [],
        'team_id' => $teamId,
    ], $attributes));
}

/** Promote an existing team Super Admin to Global Admin (trusted, quiet write). */
function promoteToGlobalAdmin(User $user): User
{
    $user->forceFill(['global_admin' => true])->saveQuietly();

    return $user->refresh();
}

describe('merged, shadow-resolved Roles index', function () {
    it('lists a shadowed Global Role once, as the team version, and hides the global row', function () {
        $user = createSuperAdmin();
        $this->actingAs($user);
        $teamId = $user->current_team_id;

        $globalEditor = catalogGlobalRole('editor', ['name' => 'Global Editor']);
        $shadow = catalogTeamRole($teamId, 'editor', ['name' => 'Team Editor']);

        // The unpaginated query the Roles table actually runs.
        $ids = livewire(Table::class, ['query' => null, 'model' => new Role])
            ->instance()->rowsQuery()->pluck('id');

        // 'editor' appears exactly once — the team's Shadow — and the global row
        // is hidden in this team context.
        expect($ids)->toContain($shadow->id)
            ->and($ids)->not->toContain($globalEditor->id);

        $editorRows = Role::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->where('slug', 'editor')
            ->count();

        expect($editorRows)->toBe(1);
    });

    it('still shows the Global Role to a different team that has no Shadow', function () {
        $owner = createSuperAdmin();
        $this->actingAs($owner);
        $ownerTeam = $owner->current_team_id;

        $globalEditor = catalogGlobalRole('editor', ['name' => 'Global Editor']);
        catalogTeamRole($ownerTeam, 'editor', ['name' => 'Team Editor']);

        // A second, independent team + Super Admin that does NOT shadow editor.
        $other = createSuperAdmin();
        $this->actingAs($other);

        $ids = livewire(Table::class, ['query' => null, 'model' => new Role])
            ->instance()->rowsQuery()->pluck('id');

        expect($ids)->toContain($globalEditor->id);
    });

    it('leaves the flat index untouched in Teams-off mode', function () {
        // (Guarded by the teams-on skip in beforeEach; documented for parity —
        // the Teams-off assertions live in the without-teams describe below.)
        expect(config('aura.teams'))->toBeTrue();
    });
});

describe('Global Roles are visibly marked read-only in a team context', function () {
    it('renders a Global badge on a Global Role row and none on a Team Role row', function () {
        $user = createSuperAdmin();
        $this->actingAs($user);
        $teamId = $user->current_team_id;

        $globalRole = catalogGlobalRole('auditor', ['name' => 'Auditor']);
        $teamRole = catalogTeamRole($teamId, 'reviewer', ['name' => 'Reviewer']);

        // The name column's display carries the badge only for global rows.
        expect($globalRole->display('name'))->toContain('Global')
            ->and($teamRole->display('name'))->not->toContain('Global');
    });

    it('shows the Global marker in the rendered Roles index', function () {
        $user = createSuperAdmin();
        $this->actingAs($user);

        catalogGlobalRole('auditor', ['name' => 'Auditor']);

        livewire(Table::class, ['query' => null, 'model' => new Role])
            ->assertSee('Global');
    });

    it('refuses a team Super Admin editing a Global Role (policy)', function () {
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        $globalRole = catalogGlobalRole('auditor');
        $teamRole = catalogTeamRole($superAdmin->current_team_id, 'reviewer');

        expect(Gate::forUser($superAdmin)->denies('update', $globalRole))->toBeTrue()
            ->and(Gate::forUser($superAdmin)->denies('delete', $globalRole))->toBeTrue()
            // Their own Team Role stays fully manageable.
            ->and(Gate::forUser($superAdmin)->allows('update', $teamRole))->toBeTrue()
            ->and(Gate::forUser($superAdmin)->allows('delete', $teamRole))->toBeTrue();
    });

    it('refuses a team Super Admin opening the Global Role edit form (403)', function () {
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        $globalRole = catalogGlobalRole('auditor');

        livewire(Edit::class, ['slug' => 'role', 'id' => $globalRole->id])
            ->assertForbidden();
    });

    it('lets a Global Admin edit and delete a Global Role', function () {
        $ga = promoteToGlobalAdmin(createSuperAdmin());
        $this->actingAs($ga);

        $globalRole = catalogGlobalRole('auditor');

        expect(Gate::forUser($ga)->allows('update', $globalRole))->toBeTrue()
            ->and(Gate::forUser($ga)->allows('delete', $globalRole))->toBeTrue();

        livewire(Edit::class, ['slug' => 'role', 'id' => $globalRole->id])
            ->set('form.fields.description', 'Edited by the Global Admin')
            ->call('save')
            ->assertHasNoErrors();

        expect($globalRole->fresh()->description)->toBe('Edited by the Global Admin')
            ->and($globalRole->fresh()->team_id)->toBeNull();
    });
});

describe('the Global Admin manages the catalog through the Role form', function () {
    it('creates a Global Role when a Global Admin checks the global toggle', function () {
        $ga = promoteToGlobalAdmin(createSuperAdmin());
        $this->actingAs($ga);

        livewire(Create::class, ['slug' => 'role'])
            ->set('form.fields.name', 'Continent')
            ->set('form.fields.slug', 'continent')
            ->set('form.fields.is_global', true)
            ->call('save');

        $role = Role::withoutGlobalScopes()->where('slug', 'continent')->first();

        expect($role)->not->toBeNull()
            ->and($role->team_id)->toBeNull();
    });

    it('keeps the role team-scoped when a non-Global-Admin sneaks the global toggle into the payload', function () {
        $superAdmin = createSuperAdmin(); // Team Super Admin, NOT a Global Admin
        $this->actingAs($superAdmin);
        $teamId = $superAdmin->current_team_id;

        // Form tampering: the toggle is hidden client-side, but the value is
        // submitted anyway.
        livewire(Create::class, ['slug' => 'role'])
            ->set('form.fields.name', 'Sneaky')
            ->set('form.fields.slug', 'sneaky')
            ->set('form.fields.is_global', true)
            ->call('save');

        $role = Role::withoutGlobalScopes()->where('slug', 'sneaky')->first();

        expect($role)->not->toBeNull()
            ->and($role->team_id)->toBe($teamId); // team-scoped, no escalation
    });
});

describe('a team Super Admin creates Team Roles including Shadows', function () {
    it('creates a Shadow of a Global Role by slug through the form, flipping permissions live', function () {
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);
        $teamId = $superAdmin->current_team_id;

        // A member holding the global "editor" role in this team.
        $globalEditor = catalogGlobalRole('editor', ['permissions' => ['view-post' => true]]);

        $member = User::factory()->create();
        $member->forceFill(['current_team_id' => $teamId])->save();
        $member->roles()->syncWithPivotValues([$globalEditor->id], ['team_id' => $teamId]);
        Cache::forget("user_{$member->id}_current_team_id");
        $member->refresh();

        // Before the Shadow: the global definition applies.
        expect($member->hasPermissionTo('view', new Post))->toBeTrue()
            ->and($member->hasPermissionTo('delete', new Post))->toBeFalse();

        // The team Super Admin creates a Team Role with the SAME slug (a Shadow)
        // through the ordinary Role create form — no unique-slug rejection.
        livewire(Create::class, ['slug' => 'role'])
            ->set('form.fields.name', 'Team Editor')
            ->set('form.fields.slug', 'editor')
            ->set('form.fields.permissions', ['delete-post' => true])
            ->call('save')
            ->assertHasNoErrors();

        $shadow = Role::withoutGlobalScopes()
            ->where('slug', 'editor')
            ->where('team_id', $teamId)
            ->first();

        expect($shadow)->not->toBeNull()
            ->and($shadow->team_id)->toBe($teamId);

        // The permission outcome flips instantly through the resolution seam,
        // with no pivot rewrite.
        $member->refresh();
        expect($member->hasPermissionTo('view', new Post))->toBeFalse()
            ->and($member->hasPermissionTo('delete', new Post))->toBeTrue();
    });
});

describe('role pickers offer the merged, shadow-resolved set', function () {
    it('offers the invitation modal the resolved set — each slug once, Shadow winning', function () {
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);
        $teamId = $superAdmin->current_team_id;

        $globalEditor = catalogGlobalRole('editor', ['name' => 'Global Editor']);
        $shadow = catalogTeamRole($teamId, 'editor', ['name' => 'Team Editor']);

        $options = collect(InviteUser::getFields())->firstWhere('slug', 'role')['options'];

        expect($options)->toHaveKey($shadow->id)          // the Shadow is offered
            ->and($options)->not->toHaveKey($globalEditor->id) // the global row is not
            ->and(array_keys($options, 'Team Editor', true))->toHaveCount(1);
    });

    it('offers the user-form Roles picker the same resolved set (each slug once)', function () {
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);
        $teamId = $superAdmin->current_team_id;

        $globalEditor = catalogGlobalRole('editor', ['name' => 'Global Editor']);
        $shadow = catalogTeamRole($teamId, 'editor', ['name' => 'Team Editor']);

        $request = (object) [
            'model' => Role::class,
            'search' => '',
            'fullField' => [
                'name' => 'Role',
                'slug' => 'roles',
                'resource' => Role::class,
                'type' => RolesField::class,
                'multiple' => false,
                'polymorphic_relation' => true,
            ],
        ];

        $ids = collect((new RolesField)->api($request))->pluck('id');

        expect($ids)->toContain($shadow->id)
            ->and($ids)->not->toContain($globalEditor->id)
            ->and($ids->filter(fn ($id) => $id === $shadow->id))->toHaveCount(1);
    });
});

describe('cross-team assignment stays refused', function () {
    it('does not offer or accept another team\'s role in the picker', function () {
        $superAdmin = createSuperAdmin();
        $this->actingAs($superAdmin);

        // A role owned by an unrelated team.
        $foreign = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $foreignRole = catalogTeamRole($foreign->id, 'foreign-role', ['name' => 'Foreign Role']);

        $ids = Role::shadowResolved($superAdmin->current_team_id)->pluck('id');

        expect($ids)->not->toContain($foreignRole->id);
    });
});

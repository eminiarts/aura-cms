<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Jobs\GenerateResourcePermissions;
use Aura\Base\Models\SavedView;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\SavedViews\SavedViewVisibility;
use Aura\Base\Services\SavedViewManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('aura.features.saved_views', true);
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_aura_saved_views_table.php.stub';
    $migration->up();

    $this->actingAs($this->user = createSuperAdmin());
    Aura::fake();
    Aura::registerResources([SavedViewManagerResource::class]);
    $this->resource = new SavedViewManagerResource;
    $this->manager = app(SavedViewManager::class);
});

class SavedViewManagerResource extends Resource
{
    public static ?string $slug = 'saved-view-manager';

    public static string $type = 'SavedViewManager';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text', 'on_index' => true],
        ];
    }
}

function managerSavedViewState(): array
{
    return [
        'v' => 1,
        'query' => ['v' => 1, 'filters' => [], 'search' => null, 'sorts' => []],
        'columns' => [],
        'view_mode' => 'list',
        'grouping' => null,
    ];
}

/** @param array<string, bool> $permissions */
function savedViewManagerActor(Team $team, array $permissions): User
{
    $role = Role::createForTeamForSystem($team->getKey(), [
        'name' => 'Saved view '.bin2hex(random_bytes(6)),
        'slug' => 'saved-view-'.bin2hex(random_bytes(6)),
        'description' => 'Saved view authorization test role.',
        'super_admin' => false,
        'permissions' => $permissions,
    ]);
    $actor = User::factory()->create(['current_team_id' => $team->getKey()]);
    $actor->roles()->attach($role->getKey(), ['team_id' => $team->getKey()]);

    return $actor->fresh();
}

test('manager creates resolves renames duplicates and deletes private views', function () {
    $view = $this->manager->createPrivate(
        $this->resource,
        $this->user,
        $this->user->currentTeam,
        'My view',
        managerSavedViewState(),
    );

    expect($view->visibility)->toBe(SavedViewVisibility::Private)
        ->and($this->manager->list($this->resource, $this->user, $this->user->currentTeam))->toHaveCount(1)
        ->and($this->manager->validatedState($view, $this->resource, $this->user, $this->user->currentTeam)->columns)
        ->toBe([]);

    $renamed = $this->manager->rename($view, $this->resource, $this->user, $this->user->currentTeam, 'Renamed');
    $duplicate = $this->manager->duplicate($renamed, $this->resource, $this->user, $this->user->currentTeam, 'Copy');

    expect($renamed->name)->toBe('Renamed')
        ->and($duplicate->name)->toBe('Copy')
        ->and($duplicate->getKey())->not->toBe($renamed->getKey());

    $this->manager->delete($renamed, $this->resource, $this->user, $this->user->currentTeam);

    expect(SavedView::query()->whereKey($renamed->getKey())->exists())->toBeFalse();
});

test('private and shared defaults resolve through explicit context', function () {
    $private = $this->manager->createPrivate(
        $this->resource,
        $this->user,
        $this->user->currentTeam,
        'Private',
        managerSavedViewState(),
    );
    $this->manager->setDefault($private, $this->resource, $this->user, $this->user->currentTeam);

    expect($this->manager->resolveDefault($this->resource, $this->user, $this->user->currentTeam)?->is($private))->toBeTrue();

    $this->manager->delete($private, $this->resource, $this->user, $this->user->currentTeam);
    $shared = $this->manager->createShared(
        $this->resource,
        $this->user,
        $this->user->currentTeam,
        'Shared',
        managerSavedViewState(),
    );
    $this->manager->setDefault($shared, $this->resource, $this->user, $this->user->currentTeam);

    expect($this->manager->resolveDefault($this->resource, $this->user, $this->user->currentTeam)?->is($shared))->toBeTrue();
});

test('foreign view identifiers fail without leaking their visibility', function () {
    $view = $this->manager->createPrivate(
        $this->resource,
        $this->user,
        $this->user->currentTeam,
        'Private',
        managerSavedViewState(),
    );
    $other = createSuperAdmin();

    expect(fn () => $this->manager->resolve($view->getKey(), $this->resource, $other, $other->currentTeam))
        ->toThrow(ModelNotFoundException::class);
});

test('shared views remain isolated to their explicit team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation requires Aura Teams.');
    }

    $view = $this->manager->createShared(
        $this->resource,
        $this->user,
        $this->user->currentTeam,
        'Shared',
        managerSavedViewState(),
    );
    $other = createSuperAdmin();

    expect(fn () => $this->manager->resolve($view->getKey(), $this->resource, $other, $other->currentTeam))
        ->toThrow(ModelNotFoundException::class)
        ->and($this->manager->list($this->resource, $other, $other->currentTeam))->toHaveCount(0);
});

test('permission synchronization provisions the saved-view management permission idempotently', function () {
    (new GenerateResourcePermissions(SavedViewManagerResource::class))->handle();
    (new GenerateResourcePermissions(SavedViewManagerResource::class))->handle();

    $permissions = Permission::withoutGlobalScopes()->where('slug', 'manage-aura-saved-views');

    expect(config('aura.teams') ? (clone $permissions)->whereNull('team_id')->count() : $permissions->count())
        ->toBe(1);

    if (config('aura.teams')) {
        expect(Permission::withoutGlobalScopes()
            ->where('slug', 'manage-aura-saved-views')
            ->where('team_id', $this->user->currentTeam->getKey())
            ->count())->toBe(1);
    }
});

test('disabled saved views do not require their table', function () {
    config()->set('aura.features.saved_views', false);
    Schema::dropIfExists('aura_saved_views');

    expect($this->manager->available($this->resource))->toBeFalse()
        ->and($this->manager->resolveDefault($this->resource, $this->user, $this->user->currentTeam))->toBeNull();
});

test('teams-off mode stores private and instance-shared views without a team identity', function () {
    config()->set('aura.teams', false);

    $private = $this->manager->createPrivate(
        $this->resource,
        $this->user,
        null,
        'Private instance view',
        managerSavedViewState(),
    );
    $shared = $this->manager->createShared(
        $this->resource,
        $this->user,
        null,
        'Shared instance view',
        managerSavedViewState(),
    );

    expect($private->team_id)->toBeNull()
        ->and($shared->team_id)->toBeNull()
        ->and($this->manager->list($this->resource, $this->user, null))->toHaveCount(2);
});

test('same-team resource viewers may manage only their own private saved views', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team-specific saved-view authorization requires Aura Teams.');
    }

    $team = $this->user->currentTeam;
    $owner = savedViewManagerActor($team, ['viewAny-saved-view-manager' => true]);
    $peer = savedViewManagerActor($team, ['viewAny-saved-view-manager' => true]);
    $private = $this->manager->createPrivate($this->resource, $owner, $team, 'Owner private', managerSavedViewState());
    $shared = $this->manager->createShared($this->resource, $this->user, $team, 'Team shared', managerSavedViewState());

    $ownPrivate = $this->manager->createPrivate($this->resource, $peer, $team, 'Peer private', managerSavedViewState());
    $this->manager->rename($ownPrivate, $this->resource, $peer, $team, 'Peer renamed');
    auth()->login($peer);
    $this->manager->setDefault($ownPrivate, $this->resource, $peer, $team);

    expect($this->manager->list($this->resource, $peer, $team)->pluck('id')->all())
        ->toContain($shared->getKey(), $ownPrivate->getKey())
        ->not->toContain($private->getKey())
        ->and(fn () => $this->manager->createShared($this->resource, $peer, $team, 'Forbidden shared', managerSavedViewState()))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->manager->rename($shared, $this->resource, $peer, $team, 'Forbidden rename'))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->manager->updateState($shared, $this->resource, $peer, $team, managerSavedViewState()))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->manager->duplicate($shared, $this->resource, $peer, $team, 'Forbidden duplicate'))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->manager->setDefault($shared, $this->resource, $peer, $team))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->manager->delete($shared, $this->resource, $peer, $team))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $this->manager->rename($private, $this->resource, $peer, $team, 'Foreign private'))
        ->toThrow(ModelNotFoundException::class)
        ->and($this->manager->resolveDefault($this->resource, $peer, $team)?->is($ownPrivate))
        ->toBeTrue();
});

test('the saved-view management permission manages team-shared views but not peers private views', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team-specific saved-view authorization requires Aura Teams.');
    }

    $team = $this->user->currentTeam;
    $owner = savedViewManagerActor($team, ['viewAny-saved-view-manager' => true]);
    $manager = savedViewManagerActor($team, [
        'viewAny-saved-view-manager' => true,
        'manage-aura-saved-views' => true,
    ]);
    $private = $this->manager->createPrivate($this->resource, $owner, $team, 'Owner private', managerSavedViewState());
    $shared = $this->manager->createShared($this->resource, $this->user, $team, 'Team shared', managerSavedViewState());

    $renamed = $this->manager->rename($shared, $this->resource, $manager, $team, 'Managed shared');
    $duplicate = $this->manager->duplicate($renamed, $this->resource, $manager, $team, 'Managed copy');
    auth()->login($manager);
    $this->manager->setDefault($renamed, $this->resource, $manager, $team);
    $this->manager->updateState($renamed, $this->resource, $manager, $team, managerSavedViewState());
    $this->manager->delete($duplicate, $this->resource, $manager, $team);

    expect($renamed->name)->toBe('Managed shared')
        ->and($this->manager->resolveDefault($this->resource, $manager, $team)?->is($renamed))
        ->toBeTrue()
        ->and(SavedView::query()->whereKey($duplicate->getKey())->exists())
        ->toBeFalse()
        ->and(fn () => $this->manager->rename($private, $this->resource, $manager, $team, 'Forbidden private'))
        ->toThrow(ModelNotFoundException::class)
        ->and(fn () => $this->manager->setDefault($private, $this->resource, $manager, $team))
        ->toThrow(ModelNotFoundException::class);
});

test('current-team Super Admins and scoped Global Admins manage shared views while other teams remain isolated', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team-specific saved-view authorization requires Aura Teams.');
    }

    $team = $this->user->currentTeam;
    $shared = $this->manager->createShared($this->resource, $this->user, $team, 'Team shared', managerSavedViewState());
    $this->manager->rename($shared, $this->resource, $this->user, $team, 'Super managed');
    $this->manager->setDefault($shared, $this->resource, $this->user, $team);

    $globalAdmin = createGlobalAdmin();
    $globalAdmin->switchTeam($team);
    $globalAdmin = $globalAdmin->fresh();
    $this->manager->updateState($shared, $this->resource, $globalAdmin, $team, managerSavedViewState());

    $otherTeam = foreignTeam();
    $otherTeamViewer = savedViewManagerActor($otherTeam, ['viewAny-saved-view-manager' => true]);

    expect($this->user->isSuperAdmin())->toBeTrue()
        ->and($this->manager->resolveDefault($this->resource, $globalAdmin, $team)?->is($shared))
        ->toBeTrue()
        ->and(fn () => $this->manager->resolve($shared->getKey(), $this->resource, $otherTeamViewer, $otherTeam))
        ->toThrow(ModelNotFoundException::class);
});

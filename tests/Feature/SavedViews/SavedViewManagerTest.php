<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Jobs\GenerateResourcePermissions;
use Aura\Base\Models\SavedView;
use Aura\Base\Resource;
use Aura\Base\Resources\Permission;
use Aura\Base\SavedViews\SavedViewVisibility;
use Aura\Base\Services\SavedViewManager;
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

    expect(Permission::withoutGlobalScopes()
        ->where('slug', 'manage-aura-saved-views')
        ->whereNull('team_id')
        ->count())->toBe(1)
        ->and(Permission::withoutGlobalScopes()
            ->where('slug', 'manage-aura-saved-views')
            ->where('team_id', $this->user->currentTeam->getKey())
            ->count())->toBe(1);
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

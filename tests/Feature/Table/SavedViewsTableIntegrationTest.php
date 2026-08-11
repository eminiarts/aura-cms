<?php

use Aura\Base\BaseResource;
use Aura\Base\Contracts\DeclaresTableParentScopes;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\SavedView;
use Aura\Base\Resource;
use Aura\Base\Services\SavedViewManager;
use Aura\Base\Table\TableParentScope;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config()->set('aura.features.saved_views', true);
    (require dirname(__DIR__, 3).'/database/migrations/create_aura_saved_views_table.php.stub')->up();
    $this->actingAs($this->user = createSuperAdmin());
    Aura::fake();
    Aura::registerResources([SavedViewsTableResource::class]);
});

class SavedViewsTableResource extends Resource implements DeclaresTableParentScopes
{
    public static ?string $slug = 'saved-views-table';

    public static string $type = 'SavedViewsTable';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text', 'on_index' => true],
        ];
    }

    public function tableParentScopes(): array
    {
        return [
            TableParentScope::foreignKey('parent', self::class, 'parent_id'),
        ];
    }
}

class SavedViewsLegacyTableResource extends BaseResource
{
    public static ?string $slug = 'saved-views-legacy-table';

    public static string $type = 'SavedViewsLegacyTable';

    protected $table = 'posts';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

test('table saves and restores an authorized canonical view', function () {
    $component = livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
    ])->set('savedViewName', 'Qualified leads')
        ->call('saveCurrentView')
        ->assertHasNoErrors();

    $savedView = SavedView::query()->sole();

    expect($component->get('savedViewId'))->toBe($savedView->getKey())
        ->and($savedView->state['query']['v'])->toBe(1);

    livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
        'savedViewId' => $savedView->getKey(),
    ])->assertSet('savedViewName', 'Qualified leads')
        ->assertSet('savedViewId', $savedView->getKey())
        ->assertSee('Qualified leads');
});

test('table does not render saved-view controls when disabled', function () {
    config()->set('aura.features.saved_views', false);

    livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
    ])->assertDontSee('Saved view');
});

test('table persists its locked required parent in saved state', function () {
    $parent = SavedViewsTableResource::create([
        'title' => 'Parent',
        'type' => SavedViewsTableResource::$type,
        'status' => 'publish',
    ]);

    livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
        'requiredParentScope' => ['scope' => 'parent', 'id' => $parent->getKey()],
    ])->set('savedViewName', 'Nested')
        ->call('saveCurrentView')
        ->assertHasNoErrors();

    expect(SavedView::query()->sole()->state['query']['parent'])->toBe([
        'scope' => 'parent',
        'id' => $parent->getKey(),
    ]);
});

test('table rejects a saved view from another required parent', function () {
    $parent = SavedViewsTableResource::create([
        'title' => 'Parent',
        'type' => SavedViewsTableResource::$type,
        'status' => 'publish',
    ]);
    $otherParent = SavedViewsTableResource::create([
        'title' => 'Other parent',
        'type' => SavedViewsTableResource::$type,
        'status' => 'publish',
    ]);

    livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
        'requiredParentScope' => ['scope' => 'parent', 'id' => $otherParent->getKey()],
    ])->set('savedViewName', 'Other nested')
        ->call('saveCurrentView');
    $savedView = SavedView::query()->sole();

    livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
        'requiredParentScope' => ['scope' => 'parent', 'id' => $parent->getKey()],
        'savedViewId' => $savedView->getKey(),
    ])->assertStatus(422);
});

test('stale defaults are unavailable without breaking table mount or the view list', function () {
    $manager = app(SavedViewManager::class);
    $view = $manager->createPrivate(
        new SavedViewsTableResource,
        $this->user,
        $this->user->currentTeam,
        'Stale default',
        [
            'v' => 1,
            'query' => ['v' => 1, 'filters' => [], 'search' => null, 'sorts' => []],
            'columns' => [],
            'view_mode' => 'list',
            'grouping' => null,
        ],
    );
    $manager->setDefault($view, new SavedViewsTableResource, $this->user, $this->user->currentTeam);
    $view->forceFill([
        'schema_version' => 99,
        'state' => [...$view->state, 'v' => 99],
    ])->saveOrFail();

    livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
    ])->assertSet('savedViewId', null)
        ->assertDontSee('Stale default');

    livewire(Table::class, [
        'model' => new SavedViewsTableResource,
        'query' => null,
        'savedViewId' => $view->getKey(),
    ])->assertStatus(422);
});

test('private and shared defaults remain isolated to their exact parent scope', function () {
    $manager = app(SavedViewManager::class);
    $resource = new SavedViewsTableResource;
    $parentA = ['scope' => 'parent', 'id' => 101];
    $parentB = ['scope' => 'parent', 'id' => 202];
    $state = fn (array $parent): array => [
        'v' => 1,
        'query' => ['v' => 1, 'filters' => [], 'search' => null, 'sorts' => [], 'parent' => $parent],
        'columns' => [],
        'view_mode' => 'list',
        'grouping' => null,
    ];

    $privateA = $manager->createPrivate($resource, $this->user, $this->user->currentTeam, 'Private A', $state($parentA));
    $privateB = $manager->createPrivate($resource, $this->user, $this->user->currentTeam, 'Private B', $state($parentB));
    $manager->setDefault($privateA, $resource, $this->user, $this->user->currentTeam, $parentA);
    $manager->setDefault($privateB, $resource, $this->user, $this->user->currentTeam, $parentB);

    expect($manager->resolveDefault($resource, $this->user, $this->user->currentTeam, $parentA)?->is($privateA))->toBeTrue()
        ->and($manager->resolveDefault($resource, $this->user, $this->user->currentTeam, $parentB)?->is($privateB))->toBeTrue()
        ->and($manager->resolveDefault($resource, $this->user, $this->user->currentTeam))->toBeNull();

    $manager->delete($privateA, $resource, $this->user, $this->user->currentTeam);
    $manager->delete($privateB, $resource, $this->user, $this->user->currentTeam);
    $sharedA = $manager->createShared($resource, $this->user, $this->user->currentTeam, 'Shared A', $state($parentA));
    $sharedB = $manager->createShared($resource, $this->user, $this->user->currentTeam, 'Shared B', $state($parentB));
    $manager->setDefault($sharedA, $resource, $this->user, $this->user->currentTeam, $parentA);
    $manager->setDefault($sharedB, $resource, $this->user, $this->user->currentTeam, $parentB);

    expect($manager->resolveDefault($resource, $this->user, $this->user->currentTeam, $parentA)?->is($sharedA))->toBeTrue()
        ->and($manager->resolveDefault($resource, $this->user, $this->user->currentTeam, $parentB)?->is($sharedB))->toBeTrue();
});

test('saved-view actions fail closed for legacy base resources', function () {
    livewire(Table::class, [
        'model' => new SavedViewsLegacyTableResource,
        'query' => null,
    ])->set('savedViewName', 'Forged legacy view')
        ->call('saveCurrentView')
        ->assertStatus(404);
});

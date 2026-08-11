<?php

use Aura\Base\Contracts\DeclaresTableParentScopes;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\SavedView;
use Aura\Base\Resource;
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

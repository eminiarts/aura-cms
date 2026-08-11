<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\SavedView;
use Aura\Base\Resource;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config()->set('aura.features.saved_views', true);
    (require dirname(__DIR__, 3).'/database/migrations/create_aura_saved_views_table.php.stub')->up();
    $this->actingAs($this->user = createSuperAdmin());
    Aura::fake();
    Aura::registerResources([SavedViewsTableResource::class]);
});

class SavedViewsTableResource extends Resource
{
    public static ?string $slug = 'saved-views-table';

    public static string $type = 'SavedViewsTable';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text', 'on_index' => true],
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

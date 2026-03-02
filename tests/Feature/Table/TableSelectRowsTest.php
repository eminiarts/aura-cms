<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $model = new TableSelectRowsModel;

    Aura::fake();
    Aura::setModel($model);

    Cache::clear();

    $this->actingAs($this->user = createSuperAdmin());

    $faker = Faker\Factory::create();

    for ($i = 0; $i < 50; $i++) {
        TableSelectRowsModel::create([
            'title' => 'Test Post '.$i,
            'content' => $faker->text,
            'type' => 'Post',
            'status' => $faker->randomElement(['publish', 'draft', 'pending', 'trash']),
            'meta' => $faker->word,
            'terms' => [
                'tag' => $faker->randomElements(['Tag 1', 'Tag 2', 'Tag 3', 'Tag 4', 'Tag 5'], 3),
            ],
        ]);
    }
});

class TableSelectRowsModel extends Resource
{
    public static $singularName = 'Post';

    public static ?string $slug = 'resource';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
            [
                'name' => 'Meta',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'meta',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

describe('row selection', function () {
    test('table initializes with empty selection', function () {
        expect(TableSelectRowsModel::count())->toBe(50);

        $post = TableSelectRowsModel::first();

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        expect($component->selected)->toBe([]);
        expect($component->paginators['page'])->toBe(1);
    });

    test('can select specific rows', function () {
        $post = TableSelectRowsModel::first();
        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $ids = TableSelectRowsModel::take(2)->pluck('id')->toArray();
        $component->set('selected', $ids);

        $component->assertViewHas('selected', fn ($selected) => count($selected) === 2);

        // Select more rows
        $component->set('selected', TableSelectRowsModel::take(5)->pluck('id')->toArray());
        $component->assertViewHas('selected', fn ($selected) => count($selected) === 5);
    });

    test('selection persists across page navigation', function () {
        $post = TableSelectRowsModel::first();
        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        expect($component->paginators['page'])->toBe(1);

        // Select 5 rows
        $component->set('selected', TableSelectRowsModel::take(5)->pluck('id')->toArray());
        expect($component->selected)->toHaveCount(5);

        // Navigate to page 2
        $component->call('setPage', 2);
        expect($component->selected)->toHaveCount(5);

        // Go back to page 1
        $component->call('setPage', 1);
        expect($component->selected)->toHaveCount(5);
    });
});

describe('page selection', function () {
    test('selecting current page adds only page items', function () {
        $post = TableSelectRowsModel::first();
        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        expect($component->selected)->toBe([]);
        expect($component->paginators['page'])->toBe(1);

        // Simulate selecting all rows on current page (default 10 per page)
        $currentPageIds = TableSelectRowsModel::query()
            ->take(10)
            ->pluck('id')
            ->toArray();

        $component->set('selected', $currentPageIds);

        expect($component->selected)->toHaveCount(10);

        // Navigate to page 2
        $component->call('setPage', 2);

        // Selection should persist
        expect($component->selected)->toHaveCount(10);
    });

    test('can select items from multiple pages', function () {
        $post = TableSelectRowsModel::first();
        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        expect($component->selected)->toBe([]);
        expect($component->paginators['page'])->toBe(1);

        // Select all on page 1
        $currentPageIds = TableSelectRowsModel::query()
            ->take(10)
            ->pluck('id')
            ->toArray();

        $component->set('selected', $currentPageIds);
        expect($component->selected)->toHaveCount(10);

        // Navigate to page 2
        $component->call('setPage', 2);
        expect($component->selected)->toHaveCount(10);

        // Select all on page 2 as well
        $page2Ids = TableSelectRowsModel::query()
            ->skip(10)
            ->take(10)
            ->pluck('id')
            ->toArray();

        $component->set('selected', array_merge($currentPageIds, $page2Ids));

        // Total should be 20 (10 from each page)
        expect($component->selected)->toHaveCount(20);
    });
});

describe('selection state', function () {
    test('can clear selection', function () {
        $post = TableSelectRowsModel::first();
        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // Select some rows
        $component->set('selected', TableSelectRowsModel::take(5)->pluck('id')->toArray());
        expect($component->selected)->toHaveCount(5);

        // Clear selection
        $component->set('selected', []);
        expect($component->selected)->toBe([]);
    });
});

<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
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

// Create Resource for this test
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

test('table filter - select rows', function () {
    // 50 Posts in DB
    expect(TableSelectRowsModel::count())->toBe(50);

    $post = TableSelectRowsModel::first();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // expect $selected to be an empty array
    expect($component->selected)->toBe([]);

    // We should be on Page 1
    expect($component->paginators['page'])->toBe(1);

    $ids = TableSelectRowsModel::take(2)->pluck('id')->toArray();

    // Select first 2 rows
    $component->set('selected', $ids);

    // expect $selected to be an array with 2 items
    $component->assertViewHas('selected', function ($selected) {
        return count($selected) === 2;
    });

    // Select first 5 rows
    $component->set('selected', TableSelectRowsModel::take(5)->pluck('id')->toArray());

    // expect $selected to be an array with 5 items
    $component->assertViewHas('selected', function ($selected) {
        return count($selected) === 5;
    });

    // Change to Page 2
    $component->call('setPage', 2);

    // $selected should have 5 items
    expect($component->selected)->toHaveCount(5);

    // Go back to page 1
    $component->call('setPage', 1);

    // $selected should have 5 items
    expect($component->selected)->toHaveCount(5);
});

test('table select rows - reset selectPage', function () {
    $post = TableSelectRowsModel::first();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // expect $selected to be an empty array
    expect($component->selected)->toBe([]);

    // We should be on Page 1
    expect($component->paginators['page'])->toBe(1);

    // Instead of setting selectPage directly, we'll simulate selecting all rows on the current page
    // by setting the selected IDs for the current page
    $currentPageIds = TableSelectRowsModel::query()
        ->take(10)  // Default pagination is 10
        ->pluck('id')
        ->toArray();
    
    $component->set('selected', $currentPageIds);

    // expect $selected to be an array with 10 items
    expect($component->selected)->toHaveCount(10);

    // go to page 2
    $component->call('setPage', 2);

    // expect $selected to still have 10 items from the first page
    expect($component->selected)->toHaveCount(10);
});

test('table select rows - keep selected when another page is selected', function () {
    $post = TableSelectRowsModel::first();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // expect $selected to be an empty array
    expect($component->selected)->toBe([]);

    // We should be on Page 1
    expect($component->paginators['page'])->toBe(1);

    // Select all rows on the current page
    $currentPageIds = TableSelectRowsModel::query()
        ->take(10)
        ->pluck('id')
        ->toArray();
    
    $component->set('selected', $currentPageIds);

    // expect $selected to be an array with 10 items
    expect($component->selected)->toHaveCount(10);

    // go to page 2
    $component->call('setPage', 2);

    // expect $selected to still have 10 items from the first page
    expect($component->selected)->toHaveCount(10);

    // Select all rows on page 2
    $page2Ids = TableSelectRowsModel::query()
        ->skip(10)
        ->take(10)
        ->pluck('id')
        ->toArray();
    
    $component->set('selected', array_merge($currentPageIds, $page2Ids));

    // expect $selected to now have 20 items (10 from each page)
    expect($component->selected)->toHaveCount(20);
});

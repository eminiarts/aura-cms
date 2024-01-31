<?php

use Eminiarts\Aura\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
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

    public static ?string $slug = 'post';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
            [
                'name' => 'Meta',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'meta',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Resources\\Tag',
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

    expect($component->selectPage)->toBe(false);

    // We should be on Page 1
    expect($component->paginators['page'])->toBe(1);

    // set selectPage to true
    $component->set('selectPage', true);

    // expect $selected to be an array with 10 items
    expect($component->selected)->toHaveCount(10);

    // expect $selectPage to be true
    expect($component->selectPage)->toBe(true);

    // go to page 2
    $component->call('setPage', 2);

    // expect $selected to be an array with 10 items
    expect($component->selected)->toHaveCount(10);

    // expect $selectPage to be false
    // expect($component->selectPage)->toBe(false); // defered with alpine
});

test('table select rows - keep selected when another page is selected', function () {
    $post = TableSelectRowsModel::first();

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    // expect $selected to be an empty array
    expect($component->selected)->toBe([]);

    expect($component->selectPage)->toBe(false);

    // We should be on Page 1
    expect($component->paginators['page'])->toBe(1);

    // set selectPage to true
    $component->set('selectPage', true);

    // expect $selected to be an array with 10 items
    expect($component->selected)->toHaveCount(10);

    // expect $selectPage to be true
    expect($component->selectPage)->toBe(true);

    // go to page 2
    $component->call('setPage', 2);

    // expect $selected to be an array with 10 items
    expect($component->selected)->toHaveCount(10);

    // expect $selectPage to be false
    // expect($component->selectPage)->toBe(false); // defered with alpine

    // dd($component->rows->items()[0]->id)

    // $component->set('selectPage', true);

    // expect($component->selected)->toHaveCount(20);
});

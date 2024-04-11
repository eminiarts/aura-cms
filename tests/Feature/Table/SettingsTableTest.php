<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Post;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Create a post
    $this->post = Post::factory()->create();
});

test('check default table settings', function () {
    $settings = $this->post->indexTableSettings();

    // expect($settings)->toBe([]);

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    // ray($component->settings);

    expect($component->settings)->toHaveKey('per_page', 10);
    expect($component->settings)->toHaveKey('columns');
    expect($component->settings['columns'])->toBeInstanceOf(Illuminate\Support\Collection::class);

    expect($component->settings['columns'])->toHaveCount(10);
    $columnsArray = $component->settings['columns']->toArray();

    expect(array_keys($columnsArray))->toMatchArray([
        'title', 'text', 'slug', 'image', 'number', 'date', 'description', 'tags', 'categories', 'user_id',
    ]);

    expect($component->settings)->toHaveKey('filters', true);
    expect($component->settings)->toHaveKey('search', true);
    expect($component->settings)->toHaveKey('sort');
    expect($component->settings['sort'])->toMatchArray([
        'column' => 'id',
        'direction' => 'desc',
    ]);
    expect($component->settings)->toHaveKey('settings', true);
    expect($component->settings)->toHaveKey('sort_columns', true);
    expect($component->settings)->toHaveKey('columns_global_key', false);
    expect($component->settings)->toHaveKey('columns_user_key', 'columns.Post');
    expect($component->settings)->toHaveKey('global_filters', true);
    expect($component->settings)->toHaveKey('title', true);
    expect($component->settings)->toHaveKey('selectable', true);
    expect($component->settings)->toHaveKey('default_view', 'list');
    expect($component->settings)->toHaveKey('header_before', true);
    expect($component->settings)->toHaveKey('header_after', true);
    expect($component->settings)->toHaveKey('table_before', true);
    expect($component->settings)->toHaveKey('table_after', true);
    expect($component->settings)->toHaveKey('create', true);
    expect($component->settings)->toHaveKey('actions', true);
    expect($component->settings)->toHaveKey('bulk_actions', true);
    expect($component->settings)->toHaveKey('header', true);
    expect($component->settings)->toHaveKey('views');

    $component->assertSeeHtml('wire:model.live.debounce="search"');
});

test('table settings can be modified', function () {
    $settings = [
        'per_page' => 20,
        'columns' => ['title', 'slug', 'user_id'],
        'filters' => false,
        'search' => false,
        'global_filters' => false,
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('per_page', 20);

    expect($component->settings)->toHaveKey('columns');

    expect($component->settings['columns'])->toHaveCount(3);

    expect($component->settings)->toHaveKey('filters', false);

    expect($component->settings)->toHaveKey('search', false);

    expect($component->settings)->toHaveKey('global_filters', false);

    $component->assertDontSeeHtml('wire:model.live.debounce="search"');
});

test('header settings', function () {
    $settings = ['header' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('header', true);

    $component->assertSeeHtml('<h1 class="text-3xl font-semibold">Posts</h1>');
    $component->assertSeeHtml('href="'.url('/admin/Post/create').'"');

    // Disable header

    $settings = ['header' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('header', false);

    $component->assertDontSeeHtml('<h1 class="text-3xl font-semibold">Posts</h1>');
    $component->assertDontSeeHtml('href="'.url('/admin/Post/create').'"');
});

test('create settings', function () {
    $settings = ['create' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('create', true);

    $component->assertSeeHtml('href="'.url('/admin/Post/create').'"');

    // Disable create

    $settings = ['create' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('create', false);

    $component->assertDontSeeHtml('href="'.url('/admin/Post/create').'"');

});

test('filters settings', function () {
    $settings = ['filters' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('filters', true);

    $component->assertSeeHtml('<div class="toggleFilters">');

    // Disable filters

    $settings = ['filters' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('filters', false);

    $component->assertDontSeeHtml('<div class="toggleFilters">');

});

test('selectable settings', function () {

    $settings = ['selectable' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('selectable', true);

    $component->assertSeeHtml('x-on:click="selectCurrentPage"');

    // Disable selectable

    $settings = ['selectable' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('selectable', false);

    $component->assertDontSeeHtml('x-on:click="selectCurrentPage"');

});

test('table_before settings', function () {
    Aura::registerInjectView('table_before', fn (): string => Blade::render('<h1>Table Before XYZ</h1>'));

    $settings = ['table_before' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_before', true);

    $component->assertSeeHtml('<h1>Table Before XYZ</h1>');

    // Disable table_before

    $settings = ['table_before' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_before', false);

    $component->assertDontSeeHtml('<h1>Table Before XYZ</h1>');
});

test('custom inject table_before for post', function () {
    Aura::registerInjectView('table_before_Post', fn (): string => Blade::render('<h1>Table Before Post</h1>'));

    $settings = ['table_before' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_before', true);

    $component->assertSeeHtml('<h1>Table Before Post</h1>');

    // Disable table_before

    $settings = ['table_before' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_before', false);

    $component->assertDontSeeHtml('<h1>Table Before Post</h1>');
});

test('custom inject table_after for post', function () {
    Aura::registerInjectView('table_after_Post', fn (): string => Blade::render('<h1>Table Before Post</h1>'));

    $settings = ['table_after' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_after', true);

    $component->assertSeeHtml('<h1>Table Before Post</h1>');

    // Disable table_after

    $settings = ['table_after' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_after', false);

    $component->assertDontSeeHtml('<h1>Table Before Post</h1>');
});

test('table_after settings', function () {
    Aura::registerInjectView('table_after', fn (): string => Blade::render('<h1>Table After XYZ</h1>'));

    $settings = ['table_after' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_after', true);

    $component->assertSeeHtml('<h1>Table After XYZ</h1>');

    // Disable table_after

    $settings = ['table_after' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('table_after', false);

    $component->assertDontSeeHtml('<h1>Table After XYZ</h1>');
});

test('header_before settings', function () {
    Aura::registerInjectView('header_before', fn (): string => Blade::render('<h1>Header before XYZ</h1>'));

    $settings = ['header_before' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('header_before', true);

    $component->assertSeeHtml('<h1>Header before XYZ</h1>');

    // Disable header_before

    $settings = ['header_before' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('header_before', false);

    $component->assertDontSeeHtml('<h1>Header before XYZ</h1>');
});

test('header_after settings', function () {
    Aura::registerInjectView('header_after', fn (): string => Blade::render('<h1>Header after XYZ</h1>'));

    $settings = ['header_after' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('header_after', true);

    $component->assertSeeHtml('<h1>Header after XYZ</h1>');

    // Disable header_after

    $settings = ['header_after' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('header_after', false);

    $component->assertDontSeeHtml('<h1>Header after XYZ</h1>');
});

test('search settings', function () {
    $settings = ['search' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('search', true);

    $component->assertSeeHtml('wire:model.live.debounce="search"');

    // Disable search

    $settings = ['search' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('search', false);

    $component->assertDontSeeHtml('wire:model.live.debounce="search"');
});

test('table columns settings', function () {

    $settings = ['columns' => ['title' => 'Title', 'slug' => 'Slug', 'user_id' => 'User']];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('columns');

    expect($component->settings['columns'])->toHaveCount(3);

    expect($component->settings['columns'])->toMatchArray([
        'title' => 'Title',
        'slug' => 'Slug',
        'user_id' => 'User',
    ]);

    expect($component->headers)->toHaveCount(3);

    $component->assertSeeHtml('wire:click="sortBy(\'title\')"');
    $component->assertSeeHtml('wire:click="sortBy(\'slug\')"');
    $component->assertSeeHtml('wire:click="sortBy(\'user_id\')"');

    $component->assertSeeHtml('for="column_title"');
    $component->assertSeeHtml('for="column_slug"');
    $component->assertSeeHtml('for="column_user_id"');

    $settings = ['columns' => []];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->headers)->toHaveCount(0);

    $component->assertDontSeeHtml('wire:click="sortBy(\'title\')"');
    $component->assertDontSeeHtml('wire:click="sortBy(\'slug\')"');
    $component->assertDontSeeHtml('wire:click="sortBy(\'user_id\')"');

    $component->assertDontSeeHtml('for="column_title"');
    $component->assertDontSeeHtml('for="column_slug"');
    $component->assertDontSeeHtml('for="column_user_id"');
});

// test('global_filters settings', function () {

//     $settings = ['global_filters' => true];

//     $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

//     expect($component->settings)->toHaveKey('global_filters', true);

//     $component->assertSeeHtml('wire:model.live.debounce="global_filters"');

//     // Disable global_filters

//     $settings = ['global_filters' => false];

//     $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

//     expect($component->settings)->toHaveKey('global_filters', false);

//     $component->assertDontSeeHtml('wire:model.live.debounce="global_filters"');

// });

test('views settings', function () {
    $settings = [
        'views' => [
            'table' => 'custom.table.table',
            'list' => 'custom.table.list',
            'grid' => 'custom.table.grid',
            'filter' => 'custom.table.filter',
            'header' => 'custom.table.header',
            'row' => 'custom.table.row',
            'bulkActions' => 'custom.table.bulkActions',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('views');

    expect($component->settings['views'])->toMatchArray([
        'table' => 'custom.table.index',
        'list' => 'custom.table.list',
        'grid' => 'custom.table.grid',
        'filter' => 'custom.table.filter',
        'header' => 'custom.table.header',
        'row' => 'custom.table.row',
        'bulkActions' => 'custom.table.bulkActions',
    ]);
})->throws(ViewException::class);

test('views settings - table', function () {
    $settings = [
        'views' => [
            'table' => 'custom.table.table',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

})->throws(ViewException::class);

test('views settings - list', function () {
    $settings = [
        'views' => [
            'list' => 'custom.table.list',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

})->throws(ViewException::class);

test('views settings - grid', function () {
    $settings = [
        'default_view' => 'grid',
        'views' => [
            'grid' => 'custom.table.grid',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

})->throws(ViewException::class);

test('views settings - filter', function () {
    $settings = [
        'views' => [
            'filter' => 'custom.table.filter',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

})->throws(ViewException::class);

test('views settings - header', function () {
    $settings = [
        'views' => [
            'header' => 'custom.table.header',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

})->throws(ViewException::class);

test('views settings - row', function () {
    $settings = [
        'views' => [
            'row' => 'custom.table.row',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

})->throws(ViewException::class);

test('views settings - bulkAction', function () {
    $settings = [
        'views' => [
            'bulkActions' => 'custom.table.bulkActions',
        ],
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

})->throws(ViewException::class);

test('default_view settings', function () {
    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => []]);

    expect($component->settings['views'])->toMatchArray([
        'table' => 'aura::components.table.index',
        'list' => 'aura::components.table.table',
        'grid' => false,
        'filter' => 'aura::components.table.filter',
        'header' => 'aura::components.table.header',
        'row' => 'aura::components.table.row',
        'bulkActions' => 'aura::components.table.bulkActions',
    ]);
});

test('columns_global_key settings', function () {

    $settings = [
        'columns_global_key' => 'globalPosts',
    ];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('columns_global_key');

    expect($component->headers)->toHaveKey('title');

    $option = Option::where('name', 'team.1.globalPosts')->first();

    expect($option)->toBeNull();

    // Disable columns_global_key
    $component->call('reorder', ['image', 'slug', 'title', 'user_id']);

    $option = Option::where('name', 'team.1.globalPosts')->first();

    expect($option->value)->toMatchArray([
        'image' => 'Bild',
        'slug' => 'Slug for Test',
        'title' => 'Title',
    ])->toHaveKeys(['image', 'slug', 'title']);

    expect(array_keys($option->value)[0])->toEqual('image');
    expect(array_keys($option->value)[1])->toEqual('slug');
    expect(array_keys($option->value)[2])->toEqual('title');
    expect(array_keys($option->value)[3])->toEqual('user_id');
});

test('title settings', function () {

    $settings = ['title' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('title', true);

    $component->assertSeeHtml('<h1 class="text-3xl font-semibold">Posts</h1>');

    // Disable title

    $settings = ['title' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('title', false);

    $component->assertDontSeeHtml('<h1 class="text-3xl font-semibold">Posts</h1>');

});

test('actions settings', function () {

    $settings = ['actions' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('actions', true);

    $component->assertSeeHtml('<th class="table-row-actions');
    $component->assertSeeHtml('<div class="table-context-menu"');

    // Disable actions
    $settings = ['actions' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('actions', false);

    $component->assertDontSeeHtml('<th class="table-row-actions');
    $component->assertDontSeeHtml('<div class="table-context-menu"');
});

test('bulk_actions settings', function () {

    $settings = ['bulk_actions' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('bulk_actions', true);

    $component->assertSeeHtml('<div class="bulk-actions">');

    // Disable bulk_actions

    $settings = ['bulk_actions' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('bulk_actions', false);

    $component->assertDontSeeHtml('<div class="bulk-actions">');
});

test('sort_columns settings', function () {
    $settings = ['sort_columns' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('sort_columns', true);

    $component->assertSeeHtml('<div class="cursor-move drag-handle move-table-row">');

    // Disable sort_columns

    $settings = ['sort_columns' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('sort_columns', false);

    $component->assertDontSeeHtml('<div class="cursor-move drag-handle move-table-row">');
});

test('grid view table', function () {

    $settings = ['default_view' => 'list'];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    $component->assertSeeHtml('<div class="aura-table-list-view">');
    $component->assertDontSeeHtml('<div class="aura-table-grid-view">');

    // Set to Grid View
    $settings = ['default_view' => 'grid', 'views' => ['grid' => $component->settings['views']['table']]];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    $component->assertSeeHtml('<div class="aura-table-grid-view">');
    $component->assertDontSeeHtml('<div class="aura-table-list-view">');
});

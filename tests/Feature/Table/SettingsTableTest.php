<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\User;
use Aura\Base\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Create a post
    $this->post = Post::factory()->create();
});

test('check default table settings', function () {
    $settings = $this->post->indexTableSettings();

    expect($settings)->toBe([]);

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('per_page', 10);
    expect($component->settings)->toHaveKey('columns');
    expect($component->settings['columns'])->toBeInstanceOf(Illuminate\Support\Collection::class);

    expect($component->settings['columns'])->toHaveCount(10);
    $columnsArray = $component->settings['columns']->toArray();

    expect(array_keys($columnsArray))->toMatchArray([
        "title", "text", "slug", "image", "number", "date", "description", "tags", "categories", "user_id",
    ]);

    expect($component->settings)->toHaveKey('filters', true);
    expect($component->settings)->toHaveKey('search', true);
    expect($component->settings)->toHaveKey('sort');
    expect($component->settings['sort'])->toMatchArray([
        "column" => "id",
        "direction" => "desc",
    ]);
    expect($component->settings)->toHaveKey('settings', true);
    expect($component->settings)->toHaveKey('sort_columns', true);
    expect($component->settings)->toHaveKey('sort_columns_key', false);
    expect($component->settings)->toHaveKey('sort_columns_user_key', 'columns_sort.Post');
    expect($component->settings)->toHaveKey('global_filters', true);
    expect($component->settings)->toHaveKey('title', true);
    expect($component->settings)->toHaveKey('attach', true);
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
    expect($component->settings['views'])->toMatchArray([
        "table" => "aura::components.table.table",
        "list" => "aura::components.table.list",
        "grid" => "aura::components.table.grid",
        "filter" => "aura::components.table.filter",
        "header" => "aura::components.table.header",
        "row" => "aura::components.table.row",
        "bulkActions" => "aura::components.table.bulkActions",
    ]);


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
    $component->assertSeeHtml('href="' . url('/admin/Post/create') . '"');

    // Disable header

    $settings = ['header' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('header', false);

    $component->assertDontSeeHtml('<h1 class="text-3xl font-semibold">Posts</h1>');
    $component->assertDontSeeHtml('href="' . url('/admin/Post/create') . '"');
});

test('actions settings', function () {
});

test('create settings', function () {
    $settings = ['create' => true];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('create', true);

    $component->assertSeeHtml('href="' . url('/admin/Post/create') . '"');

    // Disable create

    $settings = ['create' => false];

    $component = Livewire::test(Table::class, ['model' => $this->post, 'settings' => $settings]);

    expect($component->settings)->toHaveKey('create', false);

    $component->assertDontSeeHtml('href="' . url('/admin/Post/create') . '"');

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
});

test('table_before settings', function () {
});

test('header_before settings', function () {
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
});

test('global_filters settings', function () {
});

test('sort_columns settings', function () {
});

test('views settings', function () {
});

test('default_view settings', function () {
});

test('sort_columns_key settings', function () {
});

test('title settings', function () {
});

test('attach settings', function () {
});

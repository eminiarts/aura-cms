<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Option;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Post);

    $this->post = Post::factory()->create();
});

describe('default table settings', function () {
    test('table initializes with correct default settings', function () {
        $settings = $this->post->indexTableSettings();

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);

        expect($component->settings)
            ->toHaveKey('per_page', 10)
            ->toHaveKey('columns')
            ->toHaveKey('filters', true)
            ->toHaveKey('search', true)
            ->toHaveKey('sort')
            ->toHaveKey('settings', true)
            ->toHaveKey('sort_columns', true)
            ->toHaveKey('columns_global_key', false)
            ->toHaveKey('columns_user_key', 'columns.Post')
            ->toHaveKey('global_filters', true)
            ->toHaveKey('title', true)
            ->toHaveKey('selectable', true)
            ->toHaveKey('default_view', 'list')
            ->toHaveKey('header_before', true)
            ->toHaveKey('header_after', true)
            ->toHaveKey('table_before', true)
            ->toHaveKey('table_after', true)
            ->toHaveKey('create', true)
            ->toHaveKey('actions', true)
            ->toHaveKey('bulk_actions', true)
            ->toHaveKey('header', true)
            ->toHaveKey('views');

        expect($component->settings['columns'])->toBeInstanceOf(Illuminate\Support\Collection::class);
        expect($component->settings['columns'])->toHaveCount($this->post->inputFields()->count() - 1); // -1 for password

        $columnsArray = $component->settings['columns']->toArray();
        expect(array_keys($columnsArray))->toMatchArray([
            'id', 'title', 'text', 'slug', 'image', 'number', 'date', 'description', 'tags', 'categories', 'user_id',
        ]);

        expect($component->settings['sort'])->toMatchArray([
            'column' => 'id',
            'direction' => 'desc',
        ]);

        $component->assertSeeHtml('wire:model.live.debounce="search"');
    });

    test('table settings can be customized', function () {
        $settings = [
            'per_page' => 20,
            'columns' => ['title', 'slug', 'user_id'],
            'filters' => false,
            'search' => false,
            'global_filters' => false,
        ];

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);

        expect($component->settings)
            ->toHaveKey('per_page', 20)
            ->toHaveKey('columns')
            ->toHaveKey('filters', false)
            ->toHaveKey('search', false)
            ->toHaveKey('global_filters', false);

        expect($component->settings['columns'])->toHaveCount(3);

        $component->assertDontSeeHtml('wire:model.live.debounce="search"');
    });
});

describe('header settings', function () {
    test('header can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['header' => true]]);

        expect($component->settings)->toHaveKey('header', true);
        $component->assertSeeHtml('<h1 class="text-2xl font-semibold">Posts</h1>');
        $component->assertSeeHtml('href="'.url('/admin/post/create').'"');
    });

    test('header can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['header' => false]]);

        expect($component->settings)->toHaveKey('header', false);
        $component->assertDontSeeHtml('<h1 class="text-2xl font-semibold">Posts</h1>');
        $component->assertDontSeeHtml('href="'.url('/admin/Post/create').'"');
    });
});

describe('create button settings', function () {
    test('create button can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['create' => true]]);

        expect($component->settings)->toHaveKey('create', true);
        $component->assertSeeHtml('href="'.url('/admin/post/create').'"');
    });

    test('create button can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['create' => false]]);

        expect($component->settings)->toHaveKey('create', false);
        $component->assertDontSeeHtml('href="'.url('/admin/post/create').'"');
    });
});

describe('filters settings', function () {
    test('filters can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['filters' => true]]);

        expect($component->settings)->toHaveKey('filters', true);
        $component->assertSeeHtml('<div class="toggleFilters">');
    });

    test('filters can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['filters' => false]]);

        expect($component->settings)->toHaveKey('filters', false);
        $component->assertDontSeeHtml('<div class="toggleFilters">');
    });
});

describe('selectable settings', function () {
    test('selectable can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['selectable' => true]]);

        expect($component->settings)->toHaveKey('selectable', true);
        $component->assertSeeHtml('x-on:click="selectCurrentPage"');
    });

    test('selectable can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['selectable' => false]]);

        expect($component->settings)->toHaveKey('selectable', false);
        $component->assertDontSeeHtml('x-on:click="selectCurrentPage"');
    });
});

describe('inject view settings', function () {
    test('table_before inject view works', function () {
        Aura::registerInjectView('table_before', fn (): string => Blade::render('<h1>Table Before XYZ</h1>'));

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['table_before' => true]]);

        expect($component->settings)->toHaveKey('table_before', true);
        $component->assertSeeHtml('<h1>Table Before XYZ</h1>');
    });

    test('table_before inject view can be disabled', function () {
        Aura::registerInjectView('table_before', fn (): string => Blade::render('<h1>Table Before XYZ</h1>'));

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['table_before' => false]]);

        expect($component->settings)->toHaveKey('table_before', false);
        $component->assertDontSeeHtml('<h1>Table Before XYZ</h1>');
    });

    test('custom table_before for specific resource type works', function () {
        Aura::registerInjectView('table_before_Post', fn (): string => Blade::render('<h1>Table Before Post</h1>'));

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['table_before' => true]]);

        expect($component->settings)->toHaveKey('table_before', true);
        $component->assertSeeHtml('<h1>Table Before Post</h1>');
    });

    test('custom table_after for specific resource type works', function () {
        Aura::registerInjectView('table_after_Post', fn (): string => Blade::render('<h1>Table Before Post</h1>'));

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['table_after' => true]]);

        expect($component->settings)->toHaveKey('table_after', true);
        $component->assertSeeHtml('<h1>Table Before Post</h1>');
    });

    test('table_after inject view works', function () {
        Aura::registerInjectView('table_after', fn (): string => Blade::render('<h1>Table After XYZ</h1>'));

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['table_after' => true]]);

        expect($component->settings)->toHaveKey('table_after', true);
        $component->assertSeeHtml('<h1>Table After XYZ</h1>');
    });

    test('header_before inject view works', function () {
        Aura::registerInjectView('header_before', fn (): string => Blade::render('<h1>Header before XYZ</h1>'));

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['header_before' => true]]);

        expect($component->settings)->toHaveKey('header_before', true);
        $component->assertSeeHtml('<h1>Header before XYZ</h1>');
    });

    test('header_after inject view works', function () {
        Aura::registerInjectView('header_after', fn (): string => Blade::render('<h1>Header after XYZ</h1>'));

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['header_after' => true]]);

        expect($component->settings)->toHaveKey('header_after', true);
        $component->assertSeeHtml('<h1>Header after XYZ</h1>');
    });
});

describe('search settings', function () {
    test('search can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['search' => true]]);

        expect($component->settings)->toHaveKey('search', true);
        $component->assertSeeHtml('wire:model.live.debounce="search"');
    });

    test('search can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['search' => false]]);

        expect($component->settings)->toHaveKey('search', false);
        $component->assertDontSeeHtml('wire:model.live.debounce="search"');
    });
});

describe('columns settings', function () {
    test('custom columns can be specified', function () {
        $settings = ['columns' => ['title' => 'Title', 'slug' => 'Slug', 'user_id' => 'User']];

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);

        expect($component->settings['columns'])->toHaveCount(3);
        expect($component->settings['columns'])->toMatchArray([
            'title' => 'Title',
            'slug' => 'Slug',
            'user_id' => 'User',
        ]);
        expect($component->headers)->toHaveCount(3);

        $component
            ->assertSeeHtml('wire:click="sortBy(\'title\')"')
            ->assertSeeHtml('wire:click="sortBy(\'slug\')"')
            ->assertSeeHtml('wire:click="sortBy(\'user_id\')"')
            ->assertSeeHtml('for="column_title"')
            ->assertSeeHtml('for="column_slug"')
            ->assertSeeHtml('for="column_user_id"');
    });

    test('empty columns array hides all columns', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['columns' => []]]);

        expect($component->headers)->toHaveCount(0);

        $component
            ->assertDontSeeHtml('wire:click="sortBy(\'title\')"')
            ->assertDontSeeHtml('wire:click="sortBy(\'slug\')"')
            ->assertDontSeeHtml('wire:click="sortBy(\'user_id\')"')
            ->assertDontSeeHtml('for="column_title"')
            ->assertDontSeeHtml('for="column_slug"')
            ->assertDontSeeHtml('for="column_user_id"');
    });
});

describe('view settings', function () {
    test('custom views throw exception when view does not exist', function () {
        $settings = [
            'views' => [
                'table' => 'custom.table.list-view',
                'list' => 'custom.table.list',
                'grid' => 'custom.table.grid',
                'filter' => 'custom.table.filter',
                'header' => 'custom.table.header',
                'row' => 'custom.table.row',
                'bulkActions' => 'custom.table.bulk-actions',
            ],
        ];

        livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
    })->throws(ViewException::class);

    test('custom table view throws exception', function () {
        $settings = ['views' => ['table' => 'custom.table.list-view']];
        livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
    })->throws(ViewException::class);

    test('custom list view throws exception', function () {
        $settings = ['views' => ['list' => 'custom.table.list']];
        livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
    })->throws(ViewException::class);

    test('custom grid view throws exception', function () {
        $settings = ['default_view' => 'grid', 'views' => ['grid' => 'custom.table.grid']];
        livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
    })->throws(ViewException::class);

    test('custom filter view throws exception', function () {
        $settings = ['views' => ['filter' => 'custom.table.filter']];
        livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
    })->throws(ViewException::class);

    test('custom header view throws exception', function () {
        $settings = ['views' => ['header' => 'custom.table.header']];
        livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
    })->throws(ViewException::class);

    test('custom row view throws exception', function () {
        $settings = ['views' => ['row' => 'custom.table.row']];
        livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
    })->throws(ViewException::class);

    test('custom bulkActions view setting is accepted', function () {
        $settings = ['views' => ['bulkActions' => 'custom.table.bulk-actions']];
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);
        expect($component->settings['views'])->toHaveKey('bulkActions', 'custom.table.bulk-actions');
    });

    test('default view can be set to list', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['default_view' => 'list']]);
        expect($component->settings)->toHaveKey('default_view', 'list');
    });
});

describe('global columns settings', function () {
    test('columns_global_key saves columns globally', function () {
        $settings = ['columns_global_key' => 'globalPosts'];

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);

        expect($component->settings)->toHaveKey('columns_global_key');
        expect($component->headers)->toHaveKey('title');

        $option = Option::where('name', 'team.1.globalPosts')->first();
        expect($option)->toBeNull();

        // Reorder columns
        $component->call('reorder', ['image', 'slug', 'title', 'user_id']);

        $option = Option::where('name', 'team.1.globalPosts')->first();

        expect($option->value)
            ->toMatchArray([
                'image' => 'Bild',
                'slug' => 'Slug for Test',
                'title' => 'Title',
            ])
            ->toHaveKeys(['image', 'slug', 'title']);

        expect(array_keys($option->value)[0])->toEqual('image');
        expect(array_keys($option->value)[1])->toEqual('slug');
        expect(array_keys($option->value)[2])->toEqual('title');
        expect(array_keys($option->value)[3])->toEqual('user_id');
    });
});

describe('title settings', function () {
    test('title can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['title' => true]]);

        expect($component->settings)->toHaveKey('title', true);
        $component->assertSeeHtml('<h1 class="text-2xl font-semibold">Posts</h1>');
    });

    test('title can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['title' => false]]);

        expect($component->settings)->toHaveKey('title', false);
        $component->assertDontSeeHtml('<h1 class="text-2xl font-semibold">Posts</h1>');
    });
});

describe('actions settings', function () {
    test('actions can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['actions' => true]]);

        expect($component->settings)->toHaveKey('actions', true);
        $component->assertSeeHtml('<th class="table-row-actions');
        $component->assertSeeHtml('<div class="table-context-menu"');
    });

    test('actions can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['actions' => false]]);

        expect($component->settings)->toHaveKey('actions', false);
        $component->assertDontSeeHtml('<th class="table-row-actions');
        $component->assertDontSeeHtml('<div class="table-context-menu"');
    });
});

describe('bulk actions settings', function () {
    test('bulk_actions can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['bulk_actions' => true]]);

        expect($component->settings)->toHaveKey('bulk_actions', true);
        $component->assertSeeHtml('<div class="bulk-actions');
    });

    test('bulk_actions can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['bulk_actions' => false]]);

        expect($component->settings)->toHaveKey('bulk_actions', false);
        $component->assertDontSeeHtml('<div class="bulk-actions');
    });
});

describe('sort columns settings', function () {
    test('sort_columns can be enabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['sort_columns' => true]]);

        expect($component->settings)->toHaveKey('sort_columns', true);
        $component->assertSeeHtml('<div class="cursor-move drag-handle move-table-row">');
    });

    test('sort_columns can be disabled', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['sort_columns' => false]]);

        expect($component->settings)->toHaveKey('sort_columns', false);
        $component->assertDontSeeHtml('<div class="cursor-move drag-handle move-table-row">');
    });
});

describe('table view mode', function () {
    test('list view is default', function () {
        $component = livewire(Table::class, ['model' => $this->post, 'settings' => ['default_view' => 'list']]);

        $component->assertSeeHtml('<div class="aura-table-list-view">');
        $component->assertDontSeeHtml('<div class="aura-table-grid-view">');
    });

    test('grid view can be set', function () {
        // Use an existing view for the grid view setting
        $settings = ['default_view' => 'grid', 'views' => ['grid' => 'aura::attachment.grid']];

        $component = livewire(Table::class, ['model' => $this->post, 'settings' => $settings]);

        $component->assertSeeHtml('<div class="aura-table-grid-view">');
        $component->assertDontSeeHtml('<div class="aura-table-list-view">');
    });
});

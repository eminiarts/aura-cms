<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Tag;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new TableSaveFilterModel);

    $this->resource = TableSaveFilterModel::create([
        'title' => 'Test Post',
        'content' => 'Test Content A',
        'type' => 'Post',
        'status' => 'publish',
        'metafield' => 'B',
        'tags' => [
            'Tag 1', 'Tag 2', 'Tag 3',
        ],
    ]);

    $this->resource2 = TableSaveFilterModel::create([
        'title' => 'Test Post 2',
        'content' => 'Test Content B',
        'type' => 'Post',
        'status' => 'publish',
        'metafield' => 'A',
        'tags' => [
            'Tag 3', 'Tag 4', 'Tag 5',
        ],
    ]);
});

class TableSaveFilterModel extends Resource
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
                'slug' => 'metafield',
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

describe('saving filters', function () {
    test('filter can be saved and retrieved', function () {
        $tag1 = Tag::where('title', 'Tag 1')->first();

        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        // Add first filter group with tag filter
        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.name', 'tags');
        $component->set('filters.custom.0.filters.0.operator', 'contains');
        $component->set('filters.custom.0.filters.0.value', [$tag1->id]);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1);

        // Add second filter group
        $component->call('addFilterGroup');
        $component->set('filters.custom.1.filters.0.value', 'B');
        $component->set('filters.custom.1.filters.0.operator', 'is');

        expect($component->filters['custom'][1]['filters'][0]['name'])->toBe('metafield');

        // Attempt to save without name - should fail
        $component->call('saveFilter');
        $component->assertHasErrors('filter.name');

        // Set filter name and save
        $component->set('filter.name', 'Test Filter');
        $component->set('filter.type', 'user');
        $component->call('saveFilter');
        $component->assertHasNoErrors();

        // Verify filter is saved in database
        $db = DB::table('options')
            ->where('name', 'like', 'user.'.$this->user->id.'.Post.filters.test-filter')
            ->get();

        expect($db)->toHaveCount(1);

        // Verify filter can be retrieved
        $filters = auth()->user()->getOption($this->resource->getType().'.filters.*');

        expect($filters)
            ->toHaveCount(1)
            ->toHaveKey('test-filter')
            ->and($filters['test-filter']['custom'])->toHaveCount(2)
            ->and($filters['test-filter']['custom'][0]['filters'][0]['name'])->toBe('tags')
            ->and($filters['test-filter']['custom'][0]['filters'][0]['operator'])->toBe('contains');

        // Verify component state
        expect($filters->toArray())->toBe($component->userFilters);
        expect($component->filter['name'])->toBe('');
        expect($component->selectedFilter)->toBe('test-filter');

        // Verify filter still returns correct results
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);
    });

    test('filter name is required to save', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->set('filters.custom.0.filters.0.operator', 'is');

        $component->call('saveFilter');
        $component->assertHasErrors('filter.name');
    });
});

describe('deleting filters', function () {
    test('saved filter can be deleted', function () {
        // Insert a filter directly into the database
        DB::table('options')->insert([
            'name' => 'user.'.$this->user->id.'.Post.filters.test-filter',
            'value' => '{"custom":[{"filters":[{"name":"tags","operator":"contains","value":[303],"options":{"resource_type":"Aura\\\\Base\\\\Resources\\\\Tag"}}]}],"name":"Test Filter","public":false,"global":false,"slug":"test-filter"}',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        expect($component->userFilters)
            ->toHaveCount(1)
            ->toHaveKey('test-filter');

        $component->set('selectedFilter', 'test-filter');
        $component->assertSee('Delete Filter');

        $component->call('deleteFilter', 'test-filter');
        $component->dispatch('refreshTable');

        $component = $component->instance();

        expect($component->userFilters)->toHaveCount(0);
        expect($component->selectedFilter)->toBeNull();
        expect($component->filters)->toHaveKey('custom', []);
    });
});

describe('removing filters', function () {
    test('custom filter can be removed', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->set('filters.custom.0.filters.0.operator', 'is');

        $component->call('removeCustomFilter', 0);

        $component->assertViewHas('rows', fn ($rows) => count($rows) == 2);
        expect($component->filters['custom'])->toHaveCount(0);
    });

    test('filters can be reset', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->set('filters.custom.0.filters.0.operator', 'is');

        $component->assertSeeHtml('wire:click="resetFilter"');

        $component->call('resetFilter');

        $component->assertViewHas('rows', fn ($rows) => count($rows) == 2);
        expect($component->filters['custom'])->toHaveCount(0);
    });
});

describe('filter selection', function () {
    test('selecting saved filter applies its criteria', function () {
        // Insert a filter that matches only resource (metafield = 'B')
        DB::table('options')->insert([
            'name' => 'user.'.$this->user->id.'.Post.filters.meta-b-filter',
            'value' => '{"custom":[{"filters":[{"name":"metafield","operator":"is","value":"B","options":{}}]}],"name":"Meta B Filter","public":false,"global":false,"slug":"meta-b-filter"}',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        // Initially both resources are shown
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);

        // Select the saved filter
        $component->set('selectedFilter', 'meta-b-filter');

        // Now only the resource with metafield = 'B' should be shown
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);
    });
});

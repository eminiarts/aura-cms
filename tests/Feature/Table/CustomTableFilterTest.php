<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

afterEach(function () {
    Schema::dropIfExists('custom_projects');
    Aura::clear();
});

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Create a custom table for this test
    Schema::create('custom_projects', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('status')->nullable();
        $table->boolean('enabled')->nullable();
        $table->text('options')->nullable();
        $table->foreignId('user_id');
        $table->foreignId('team_id');
        $table->timestamps();
    });

    $model = new CustomTableFilterModel;

    Aura::fake();
    Aura::setModel($model);

    $this->resource = CustomTableFilterModel::create([
        'name' => 'Test Post 1',
        'status' => 'publish',
        'enabled' => 1,
        'options' => [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
        ],
        'terms' => [
            'tag' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ],
    ]);

    $this->resource2 = CustomTableFilterModel::create([
        'name' => 'Test Post 2',
        'status' => 'draft',
        'enabled' => 0,
        'options' => [
            'option1' => 'Option 3',
            'option2' => 'Option 4',
        ],
        'terms' => [
            'tag' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ],
    ]);
});

// Create Resource for this test
class CustomTableFilterModel extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Project';

    public static ?string $slug = 'project';

    public static string $type = 'Project';

    protected $casts = [
        'options' => 'array',
        'enabled' => 'boolean',
    ];

    protected $fillable = [
        'name', 'status', 'enabled', 'options', 'user_id', 'team_id',
    ];

    protected $table = 'custom_projects';

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'name',
            ],
            [
                'name' => 'Status',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'status',
            ],
            [
                'name' => 'Enabled',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'enabled',
            ],
            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'options',
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

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope);

        static::saving(function ($post) {
            if (! $post->team_id && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }

            if (! $post->user_id && auth()->user()) {
                $post->user_id = auth()->user()->id;
            }

            unset($post->title);
            unset($post->content);
            unset($post->type);
        });
    }
}

describe('contains filter on custom table', function () {
    test('filter by name column with contains operator', function () {
        $post = $this->resource;
        $post2 = $this->resource2;

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'contains');
        $component->set('filters.custom.0.filters.0.value', 'Post 1');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        $component->set('filters.custom.0.filters.0.value', 'Post 2');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        $component->set('filters.custom.0.filters.0.value', 'Post 3');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('filter by status column with contains operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'status');
        $component->set('filters.custom.0.filters.0.operator', 'contains');
        $component->set('filters.custom.0.filters.0.value', 'publish');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);

        $component->set('filters.custom.0.filters.0.value', 'draft');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource2->id);
    });
});

describe('does_not_contain filter on custom table', function () {
    test('filter by name column with does_not_contain operator', function () {
        $post = $this->resource;
        $post2 = $this->resource2;

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'does_not_contain');
        $component->set('filters.custom.0.filters.0.value', 'Post 1');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        $component->set('filters.custom.0.filters.0.value', 'Post 2');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        $component->set('filters.custom.0.filters.0.value', 'Post 3');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });
});

describe('starts_with filter on custom table', function () {
    test('filter by name column with starts_with operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'starts_with');
        $component->set('filters.custom.0.filters.0.value', 'Test');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) == 2);
    });

    test('filter by status column with starts_with operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'status');
        $component->set('filters.custom.0.filters.0.operator', 'starts_with');
        $component->set('filters.custom.0.filters.0.value', 'pub');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) == 1 && $rows->items()[0]->id === $this->resource->id);

        $component->set('filters.custom.0.filters.0.value', 'dra');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) == 1 && $rows->items()[0]->id === $this->resource2->id);

        $component->set('filters.custom.0.filters.0.value', 'zzz');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) == 0);
    });
});

describe('ends_with filter on custom table', function () {
    test('filter by name column with ends_with operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'ends_with');
        $component->set('filters.custom.0.filters.0.value', 'Post 1');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);

        $component->set('filters.custom.0.filters.0.value', 'Post 2');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource2->id);
    });
});

describe('is (exact match) filter on custom table', function () {
    test('filter by status column with is operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'status');
        $component->set('filters.custom.0.filters.0.operator', 'is');
        $component->set('filters.custom.0.filters.0.value', 'publish');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);

        $component->set('filters.custom.0.filters.0.value', 'draft');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource2->id);

        // Partial match should not work with 'is' operator
        $component->set('filters.custom.0.filters.0.value', 'pub');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('is_not filter on custom table', function () {
    test('filter by status column with is_not operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'status');
        $component->set('filters.custom.0.filters.0.operator', 'is_not');
        $component->set('filters.custom.0.filters.0.value', 'publish');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource2->id);

        $component->set('filters.custom.0.filters.0.value', 'draft');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);
    });
});

describe('empty filters on custom table', function () {
    test('filter by status column with is_empty operator', function () {
        // Create a resource with empty status
        $emptyStatusResource = CustomTableFilterModel::create([
            'name' => 'Empty Status Post',
            'status' => null,
            'enabled' => 1,
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'status');
        $component->set('filters.custom.0.filters.0.operator', 'is_empty');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $emptyStatusResource->id);
    });

    test('filter by status column with is_not_empty operator', function () {
        // Create a resource with empty status
        CustomTableFilterModel::create([
            'name' => 'Empty Status Post',
            'status' => null,
            'enabled' => 1,
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'status');
        $component->set('filters.custom.0.filters.0.operator', 'is_not_empty');

        // Should return 2 resources (resource and resource2 which have non-empty status)
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });
});

describe('comparison operators on custom table', function () {
    test('filter with greater_than operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'greater_than');
        $component->set('filters.custom.0.filters.0.value', 'Test Post 1');

        // String comparison: "Test Post 2" > "Test Post 1"
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource2->id);
    });

    test('filter with less_than operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'less_than');
        $component->set('filters.custom.0.filters.0.value', 'Test Post 2');

        // String comparison: "Test Post 1" < "Test Post 2"
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);
    });
});

describe('multiple filter groups on custom table', function () {
    test('can combine multiple filter groups with AND', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');
        $component->call('addFilterGroup');

        // First filter: name contains "Test"
        $component->set('filters.custom.0.filters.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'contains');
        $component->set('filters.custom.0.filters.0.value', 'Test');

        // Second filter: status is "publish" (AND operator by default)
        $component->set('filters.custom.1.filters.0.name', 'status');
        $component->set('filters.custom.1.filters.0.operator', 'is');
        $component->set('filters.custom.1.filters.0.value', 'publish');
        $component->set('filters.custom.1.operator', 'and');

        // Should return only resource (has "Test" in name AND status is "publish")
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->resource->id);
    });

    test('can combine multiple filter groups with OR', function () {
        // Create a third resource
        $resource3 = CustomTableFilterModel::create([
            'name' => 'Different Name',
            'status' => 'publish',
            'enabled' => 1,
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $this->resource]);

        $component->call('addFilterGroup');
        $component->call('addFilterGroup');

        // First filter: name contains "Post 1"
        $component->set('filters.custom.0.filters.0.name', 'name');
        $component->set('filters.custom.0.filters.0.operator', 'contains');
        $component->set('filters.custom.0.filters.0.value', 'Post 1');

        // Second filter: status is "draft" (OR operator)
        $component->set('filters.custom.1.filters.0.name', 'status');
        $component->set('filters.custom.1.filters.0.operator', 'is');
        $component->set('filters.custom.1.filters.0.value', 'draft');
        $component->set('filters.custom.1.operator', 'or');

        // Should return resource (name contains "Post 1") OR resource2 (status is "draft")
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });
});

<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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

    // Create a Posts
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

    // cast options to array
    protected $casts = [
        'options' => 'array',
        'enabled' => 'boolean',
    ];

    // set fillable fields
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

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
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

            // unset post attributes
            unset($post->title);
            unset($post->content);
            unset($post->type);
            // unset($post->team_id);
        });
    }
}

test('table filter - custom column on table - contains', function () {
    $post = $this->resource;
    $post2 = $this->resource2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilterGroup');

    // Contains
    $component->set('filters.custom.0.filters.0.name', 'name');
    $component->set('filters.custom.0.filters.0.operator', 'contains');
    $component->set('filters.custom.0.filters.0.value', 'Post 1');

    $component->assertViewHas('rows', function ($rows) use ($post) {
        return count($rows->items()) === 1 && $rows->items()[0]->id === $post->id;
    });

    $component->set('filters.custom.0.filters.0.value', 'Post 2');

    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id;
    });

    $component->set('filters.custom.0.filters.0.value', 'Post 3');

    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 0;
    });
});

test('table filter - custom column on table - does_not_contain', function () {
    $post = $this->resource;
    $post2 = $this->resource2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilterGroup');

    // Does not Contain
    $component->set('filters.custom.0.name', 'name');
    $component->set('filters.custom.0.filters.0.operator', 'does_not_contain');
    $component->set('filters.custom.0.filters.0.value', 'Post 1');

    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id;
    });

    $component->set('filters.custom.0.filters.0.value', 'Post 2');

    $component->assertViewHas('rows', function ($rows) use ($post) {
        return count($rows->items()) === 1 && $rows->items()[0]->id === $post->id;
    });

    $component->set('filters.custom.0.filters.0.value', 'Post 3');

    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) === 2;
    });
});

test('table filter - custom column on table - starts_with', function () {
    $post = $this->resource;
    $post2 = $this->resource2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilterGroup');

    // Does not Contain
    $component->set('filters.custom.0.filters.0.name', 'name');
    $component->set('filters.custom.0.filters.0.operator', 'starts_with');
    $component->set('filters.custom.0.filters.0.value', 'Test');

    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) == 2;
    });

    $component->set('filters.custom.0.filters.0.name', 'status');
    $component->set('filters.custom.0.filters.0.value', 'pub');

    $component->assertViewHas('rows', function ($rows) use ($post) {
        return count($rows->items()) == 1 && $rows->items()[0]->id === $post->id;
    });

    $component->set('filters.custom.0.filters.0.value', 'dra');

    $component->assertViewHas('rows', function ($rows) use ($post2) {
        return count($rows->items()) == 1 && $rows->items()[0]->id === $post2->id;
    });

    $component->set('filters.custom.0.filters.0.value', 'zzz');

    $component->assertViewHas('rows', function ($rows) {
        return count($rows->items()) == 0;
    });

    // Start listening to SQL queries
    // $queries = [];
    // DB::listen(function ($query) use (&$queries) {
    //     $queries[] = $query;
    // });

    // $component->call('getRows');

    // Inspect sql
    // Skip Test for now
    // expect($component->rowsQuery->toSql())->toContain('select * from "custom_projects" where "status" like ? and "custom_projects"."team_id" = ? order by "custom_projects"."id" desc');

    // expect($component->rowsQuery->getBindings()[0])->toBe('zzz%');
});

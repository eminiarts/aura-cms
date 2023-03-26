<?php

use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Traits\CustomTable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function () {
    Schema::dropIfExists('custom_projects');
});

beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);

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

    // Create a Posts
    $this->post = CustomTableFilterModel::create([
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

    $this->post2 = CustomTableFilterModel::create([
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
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'name',
            ],
            [
                'name' => 'Status',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'status',
            ],
            [
                'name' => 'Enabled',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'enabled',
            ],
            [
                'name' => 'Options',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'options',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Taxonomies\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
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
        static::addGlobalScope(new TeamScope());

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
    $post = $this->post;
    $post2 = $this->post2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    // Contains
    $component->set('filters.custom.0.name', 'name');
    $component->set('filters.custom.0.operator', 'contains');
    $component->set('filters.custom.0.value', 'Post 1');

    expect($component->rows->items())->toHaveCount(1);
    expect($component->rows->items()[0]->id)->toBe($post->id);

    $component->set('filters.custom.0.value', 'Post 2');

    expect($component->rows->items())->toHaveCount(1);
    expect($component->rows->items()[0]->id)->toBe($post2->id);

    $component->set('filters.custom.0.value', 'Post 3');

    expect($component->rows->items())->toHaveCount(0);
});

test('table filter - custom column on table - does_not_contain', function () {
    $post = $this->post;
    $post2 = $this->post2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    // Does not Contain
    $component->set('filters.custom.0.name', 'name');
    $component->set('filters.custom.0.operator', 'does_not_contain');
    $component->set('filters.custom.0.value', 'Post 1');

    expect($component->rows->items())->toHaveCount(1);
    expect($component->rows->items()[0]->id)->toBe($post2->id);

    $component->set('filters.custom.0.value', 'Post 2');

    expect($component->rows->items())->toHaveCount(1);
    expect($component->rows->items()[0]->id)->toBe($post->id);

    $component->set('filters.custom.0.value', 'Post 3');

    expect($component->rows->items())->toHaveCount(2);
});

test('table filter - custom column on table - starts_with', function () {
    $post = $this->post;
    $post2 = $this->post2;

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post]);

    $component->call('addFilter');

    // Does not Contain
    $component->set('filters.custom.0.name', 'name');
    $component->set('filters.custom.0.operator', 'starts_with');
    $component->set('filters.custom.0.value', 'Test');

    expect($component->rows->items())->toHaveCount(2);

    $component->set('filters.custom.0.name', 'status');
    $component->set('filters.custom.0.value', 'pub');

    expect($component->rows->items())->toHaveCount(1);
    expect($component->rows->items()[0]->id)->toBe($post->id);

    $component->set('filters.custom.0.value', 'dra');

    expect($component->rows->items())->toHaveCount(1);
    expect($component->rows->items()[0]->id)->toBe($post2->id);

    $component->set('filters.custom.0.value', 'zzz');

    expect($component->rows->items())->toHaveCount(0);

    // Inspect sql
    expect($component->rowsQuery->toSql())->toContain('select * from "custom_projects" where "status" like ? and "team_id" = ? order by "id" desc');

    expect($component->rowsQuery->getBindings()[0])->toBe('zzz%');
});

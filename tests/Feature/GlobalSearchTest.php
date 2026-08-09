<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

afterEach(function () {
    Schema::dropIfExists('global_search_projects');
});

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([
        GlobalSearchModel::class,
    ]);
    Aura::setModel(new GlobalSearchModel);

    $this->actingAs($this->user = createSuperAdmin());
});

class GlobalSearchModel extends Resource
{
    public static $singularName = 'SearchPost';

    public static ?string $slug = 'searchpost';

    public static string $type = 'SearchPost';

    protected static array $searchable = [
        'title' => 20,
        'content2' => 10,
    ];

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'searchable' => true,
                'slug' => 'title',
            ],
            [
                'name' => 'Content2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'searchable' => true,
                'slug' => 'content2',
            ],
            [
                'name' => 'Description',
                'type' => 'Aura\\Base\\Fields\\Text',
                'searchable' => true,
                'slug' => 'description',
            ],
        ];
    }

    public function title()
    {
        return $this->title;
    }
}

class GlobalSearchExcludedModel extends GlobalSearchModel
{
    public static $globalSearch = false;

    public static $singularName = 'Excluded Search Post';

    public static ?string $slug = 'excluded-search-post';

    public static string $type = 'ExcludedSearchPost';
}

class GlobalSearchSecondaryModel extends GlobalSearchModel
{
    public static $singularName = 'Secondary Search Post';

    public static ?string $slug = 'secondary-search-post';

    public static string $type = 'SecondarySearchPost';
}

class GlobalSearchThirdModel extends GlobalSearchModel
{
    public static $singularName = 'Third Search Post';

    public static ?string $slug = 'third-search-post';

    public static string $type = 'ThirdSearchPost';
}

class GlobalSearchMissingTitleModel extends Resource
{
    public static $singularName = 'Untitled Search Record';

    public static ?string $slug = 'untitled-search-record';

    public static string $type = 'UntitledSearchRecord';

    protected static array $searchable = ['name'];

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'searchable' => true,
                'slug' => 'name',
            ],
        ];
    }
}

class GlobalSearchNoViewModel extends GlobalSearchModel
{
    public static $singularName = 'No View Search Post';

    public static ?string $slug = 'no-view-search-post';

    public static string $type = 'NoViewSearchPost';

    public function globalSearchUrl(): ?string
    {
        return null;
    }
}

class GlobalSearchCustomTableModel extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Global Search Project';

    public static ?string $slug = 'global-search-project';

    public static string $type = 'GlobalSearchProject';

    protected $fillable = [
        'name',
        'user_id',
        'team_id',
        'created_at',
        'updated_at',
    ];

    protected $table = 'global_search_projects';

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'searchable' => true,
                'slug' => 'name',
            ],
            [
                'name' => 'Meta 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'searchable' => true,
                'slug' => 'meta_1',
            ],
        ];
    }

    public function title()
    {
        return $this->name;
    }
}

test('can find models by title', function () {
    $posts = collect([
        GlobalSearchModel::create([
            'title' => 'First Test Post',
            'content2' => 'Some content here',
            'description' => 'Not searchable field',
        ]),
        GlobalSearchModel::create([
            'title' => 'Second Test Post',
            'content2' => 'Different content',
            'description' => 'Also not searchable',
        ]),
        GlobalSearchModel::create([
            'title' => 'Third Special Post',
            'content2' => 'More test content',
            'description' => 'Hidden description',
        ]),
        GlobalSearchModel::create([
            'title' => 'Fourth Regular Post',
            'content2' => 'Regular content',
            'description' => 'Regular description',
        ]),
        GlobalSearchModel::create([
            'title' => 'Fifth Unique Post',
            'content2' => 'Unique content',
            'description' => 'Unique description',
        ]),
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Special')
        ->assertSee('Third Special Post')
        ->assertDontSee('First Test Post')
        ->assertDontSee('Second Test Post');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Unique content')
        ->assertSee('Fifth Unique Post');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Test')
        ->assertSee('First Test Post')
        ->assertSee('Second Test Post');
});

test('respects searchable field configuration', function () {
    $post = GlobalSearchModel::create([
        'title' => 'Searchable Title',
        'content2' => 'Searchable Content',
        'description' => 'Unsearchable Description',
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Searchable Title')
        ->assertSee('Searchable Title');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Searchable Content')
        ->assertSee('Searchable Title');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Unsearchable Description')
        ->assertDontSee('Searchable Title');
});

test('includes and excludes resources through the explicit resource contract', function () {
    Aura::registerResources([GlobalSearchExcludedModel::class]);
    Aura::registerRoutes(GlobalSearchExcludedModel::getSlug(), GlobalSearchExcludedModel::class);

    GlobalSearchModel::create([
        'title' => 'Included Contract Needle',
        'content2' => 'Included content',
    ]);
    GlobalSearchExcludedModel::create([
        'title' => 'Excluded Contract Needle',
        'content2' => 'Excluded content',
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Contract Needle')
        ->assertSee('Included Contract Needle')
        ->assertDontSee('Excluded Contract Needle');
});

test('requires the configured minimum query length without querying resources', function () {
    config(['aura.global_search.minimum_query_length' => 2]);

    GlobalSearchModel::create([
        'title' => 'A',
        'content2' => 'Single character',
    ]);

    $searchQueries = 0;

    DB::listen(function ($query) use (&$searchQueries): void {
        if (str_contains($query->sql, 'from "posts"') && str_contains(strtolower($query->sql), ' limit ')) {
            $searchQueries++;
        }
    });

    $component = app(GlobalSearch::class);
    $component->search = 'A';

    expect($component->getSearchResultsProperty())
        ->toBeEmpty()
        ->and($searchQueries)->toBe(0);
});

test('treats sql wildcard and escape characters as literal input', function () {
    GlobalSearchModel::create([
        'title' => 'Budget 100%_! Plan',
        'content2' => 'Literal wildcard characters',
    ]);
    GlobalSearchModel::create([
        'title' => 'Budget 100XY! other Plan',
        'content2' => 'Would match unescaped wildcards',
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', '100%_!')
        ->assertSee('Budget 100%_! Plan')
        ->assertDontSee('Budget 100XY! other Plan');
});

test('applies deterministic exact prefix contains field and key ranking', function () {
    $contains = GlobalSearchModel::create([
        'title' => 'A record containing Ranking Needle',
        'content2' => 'Other content',
    ]);
    $prefix = GlobalSearchModel::create([
        'title' => 'Ranking Needle prefix match',
        'content2' => 'Other content',
    ]);
    $contentExact = GlobalSearchModel::create([
        'title' => 'Content field exact match',
        'content2' => 'Ranking Needle',
    ]);
    $exact = GlobalSearchModel::create([
        'title' => 'Ranking Needle',
        'content2' => 'Other content',
    ]);
    $prefixTie = GlobalSearchModel::create([
        'title' => 'Ranking Needle second prefix match',
        'content2' => 'Other content',
    ]);

    $component = app(GlobalSearch::class);
    $component->search = 'Ranking Needle';

    expect($component->getSearchResultsProperty()->collapse()->pluck('id')->all())->toBe([
        $exact->id,
        $contentExact->id,
        $prefix->id,
        $prefixTie->id,
        $contains->id,
    ]);
});

test('enforces per-resource and global result limits', function () {
    config([
        'aura.global_search.global_limit' => 3,
        'aura.global_search.per_resource_limit' => 2,
    ]);

    Aura::registerResources([GlobalSearchSecondaryModel::class]);
    Aura::registerRoutes(GlobalSearchSecondaryModel::getSlug(), GlobalSearchSecondaryModel::class);

    foreach (range(1, 5) as $index) {
        GlobalSearchModel::create([
            'title' => "Bounded Needle Primary {$index}",
            'content2' => 'Primary',
        ]);
        GlobalSearchSecondaryModel::create([
            'title' => "Bounded Needle Secondary {$index}",
            'content2' => 'Secondary',
        ]);
    }

    $component = app(GlobalSearch::class);
    $component->search = 'Bounded Needle';
    $results = $component->getSearchResultsProperty();

    expect($results->get(GlobalSearchModel::getType()))->toHaveCount(2)
        ->and($results->get(GlobalSearchSecondaryModel::getType()))->toHaveCount(1)
        ->and($results->collapse())->toHaveCount(3);
});

test('bounds the number of searched resources and resulting query count', function () {
    config([
        'aura.global_search.max_resources' => 2,
        'aura.global_search.per_resource_limit' => 2,
    ]);

    Aura::registerResources([
        GlobalSearchSecondaryModel::class,
        GlobalSearchThirdModel::class,
    ]);
    Aura::registerRoutes(GlobalSearchSecondaryModel::getSlug(), GlobalSearchSecondaryModel::class);
    Aura::registerRoutes(GlobalSearchThirdModel::getSlug(), GlobalSearchThirdModel::class);

    GlobalSearchModel::create(['title' => 'Query Bound Needle', 'content2' => 'Primary']);
    GlobalSearchSecondaryModel::create(['title' => 'Query Bound Needle', 'content2' => 'Secondary']);
    GlobalSearchThirdModel::create(['title' => 'Query Bound Needle', 'content2' => 'Third']);

    $searchQueries = 0;

    DB::listen(function ($query) use (&$searchQueries): void {
        if (str_contains($query->sql, 'from "posts"') && str_contains(strtolower($query->sql), ' limit ')) {
            $searchQueries++;
        }
    });

    $component = app(GlobalSearch::class);
    $component->search = 'Query Bound Needle';
    $results = $component->getSearchResultsProperty();

    expect($searchQueries)->toBe(2)
        ->and($results)->toHaveKeys([
            GlobalSearchModel::getType(),
            GlobalSearchSecondaryModel::getType(),
        ])
        ->and($results)->not->toHaveKey(GlobalSearchThirdModel::getType());
});

test('does not materialize a full matching table', function () {
    config([
        'aura.global_search.global_limit' => 3,
        'aura.global_search.per_resource_limit' => 3,
    ]);

    $timestamp = now();
    $rows = collect(range(1, 500))->map(fn (int $index): array => [
        'title' => "Large Dataset Needle {$index}",
        'content' => null,
        'type' => GlobalSearchModel::getType(),
        'status' => 'publish',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ])->all();

    foreach (array_chunk($rows, 100) as $chunk) {
        DB::table('posts')->insert($chunk);
    }

    $retrieved = 0;
    Event::listen('eloquent.retrieved: '.GlobalSearchModel::class, function () use (&$retrieved): void {
        $retrieved++;
    });

    $component = app(GlobalSearch::class);
    $component->search = 'Large Dataset Needle';
    $results = $component->getSearchResultsProperty();

    expect($results->collapse())->toHaveCount(3)
        ->and($retrieved)->toBe(0);
});

test('searches resources whose searchable contract has no title field', function () {
    Aura::registerResources([GlobalSearchMissingTitleModel::class]);
    Aura::registerRoutes(GlobalSearchMissingTitleModel::getSlug(), GlobalSearchMissingTitleModel::class);

    $record = GlobalSearchMissingTitleModel::create([
        'name' => 'Nameless Title Needle',
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Nameless Title Needle')
        ->assertSee("UntitledSearchRecord (#{$record->id})");
});

test('omits records without an authorized view destination', function () {
    Aura::registerResources([GlobalSearchNoViewModel::class]);
    Aura::registerRoutes(GlobalSearchNoViewModel::getSlug(), GlobalSearchNoViewModel::class);

    GlobalSearchNoViewModel::create([
        'title' => 'Unauthorized Destination Needle',
        'content2' => 'No view route should be returned',
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Unauthorized Destination Needle')
        ->assertDontSee('Unauthorized Destination Needle');
});

test('can be disabled via config', function () {
    $post = GlobalSearchModel::create([
        'title' => 'Test Post',
        'content2' => 'Test Content',
        'description' => 'Test Description',
    ]);

    config(['aura.features.global_search' => true]);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('global-search');

    config(['aura.features.global_search' => false]);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('global-search');

    Livewire::test(GlobalSearch::class)
        ->assertStatus(403);
});

test('returns empty when no matches found', function () {
    $posts = collect([
        GlobalSearchModel::create([
            'title' => 'First Post',
            'content2' => 'Some content',
            'description' => 'Description',
        ]),
        GlobalSearchModel::create([
            'title' => 'Second Post',
            'content2' => 'Other content',
            'description' => 'Other description',
        ]),
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'NonExistentTerm')
        ->assertSee('No results')
        ->assertDontSee('First Post')
        ->assertDontSee('Second Post');
});

test('can find custom table resource records by table and meta fields', function () {
    Schema::create('global_search_projects', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->foreignId('user_id');
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Aura::registerResources([
        GlobalSearchCustomTableModel::class,
    ]);
    Aura::setModel(new GlobalSearchCustomTableModel);

    GlobalSearchCustomTableModel::create([
        'name' => 'Custom Search Alpha',
        'meta_1' => 'Hidden Needle',
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Custom Search Alpha')
        ->assertSee('Custom Search Alpha');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Hidden Needle')
        ->assertSee('Custom Search Alpha');
});

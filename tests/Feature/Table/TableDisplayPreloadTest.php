<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Tag;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Query-budget helpers
|--------------------------------------------------------------------------
*/

function preloadSelectsFrom(array $queries, string $table): int
{
    return collect($queries)->filter(function ($q) use ($table) {
        $sql = strtolower($q['query']);

        return str_contains($sql, 'from "'.$table.'"') || str_contains($sql, 'from `'.$table.'`');
    })->count();
}

function preloadSelectsContaining(array $queries, string $needle): int
{
    return collect($queries)->filter(function ($q) use ($needle) {
        return str_contains(strtolower($q['query']), strtolower($needle));
    })->count();
}

/*
|--------------------------------------------------------------------------
| Test resources
|--------------------------------------------------------------------------
*/

// Custom-table target of the BelongsTo relations. title() returns a
// recognizable, unique value so cross-team leakage is easy to assert.
class PreloadAuthor extends Resource
{
    public static $customTable = true;

    public static $singularName = 'Author';

    public static ?string $slug = 'preload-author';

    public static string $type = 'Author';

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'user_id', 'team_id'];

    protected $table = 'preload_authors';

    public static function getFields()
    {
        return [
            ['name' => 'Name', 'type' => 'Aura\\Base\\Fields\\Text', 'slug' => 'name', 'validation' => '', 'conditional_logic' => []],
        ];
    }

    public function title()
    {
        return $this->name;
    }
}

// Posts-table resource with a meta-backed BelongsTo column.
class PreloadBelongsToPost extends Resource
{
    public static $singularName = 'BtPost';

    public static ?string $slug = 'preload-bt-post';

    public static string $type = 'PreloadBelongsToPost';

    public static function getFields()
    {
        return [
            ['name' => 'Author', 'slug' => 'author_id', 'type' => 'Aura\\Base\\Fields\\BelongsTo', 'resource' => PreloadAuthor::class, 'validation' => '', 'conditional_logic' => [], 'on_index' => true],
        ];
    }
}

// Custom-table resource with a column-backed BelongsTo.
class PreloadBelongsToProject extends Resource
{
    public static $customTable = true;

    public static $singularName = 'BtProject';

    public static ?string $slug = 'preload-bt-project';

    public static string $type = 'PreloadBelongsToProject';

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'author_id', 'user_id', 'team_id'];

    protected $table = 'preload_projects';

    public static function getFields()
    {
        return [
            ['name' => 'Name', 'type' => 'Aura\\Base\\Fields\\Text', 'slug' => 'name', 'validation' => '', 'conditional_logic' => [], 'on_index' => true],
            ['name' => 'Author', 'slug' => 'author_id', 'type' => 'Aura\\Base\\Fields\\BelongsTo', 'resource' => PreloadAuthor::class, 'validation' => '', 'conditional_logic' => [], 'on_index' => true],
        ];
    }
}

// Posts-table resource with a visible Tags column.
class PreloadTagPost extends Resource
{
    public static $singularName = 'TagPost';

    public static ?string $slug = 'preload-tag-post';

    public static string $type = 'PreloadTagPost';

    public static function getFields()
    {
        return [
            ['name' => 'Tags', 'slug' => 'tags', 'type' => 'Aura\\Base\\Fields\\Tags', 'resource' => 'Aura\\Base\\Resources\\Tag', 'create' => true, 'validation' => '', 'conditional_logic' => [], 'on_index' => true],
        ];
    }
}

// Posts-table resource with a visible Image column.
class PreloadImagePost extends Resource
{
    public static $singularName = 'ImgPost';

    public static ?string $slug = 'preload-img-post';

    public static string $type = 'PreloadImagePost';

    public static function getFields()
    {
        return [
            ['name' => 'Image', 'slug' => 'image', 'type' => 'Aura\\Base\\Fields\\Image', 'validation' => '', 'conditional_logic' => [], 'on_index' => true],
        ];
    }
}

// Posts-table resource with a meta-backed Text field for cache-correctness.
class PreloadMetaPost extends Resource
{
    public static $singularName = 'MetaPost';

    public static ?string $slug = 'preload-meta-post';

    public static string $type = 'PreloadMetaPost';

    public static function getFields()
    {
        return [
            ['name' => 'Subtitle', 'slug' => 'subtitle', 'type' => 'Aura\\Base\\Fields\\Text', 'validation' => '', 'conditional_logic' => []],
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Setup / teardown
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Schema::create('preload_authors', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Schema::create('preload_projects', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->foreignId('author_id')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('preload_authors');
    Schema::dropIfExists('preload_projects');

    Aura::clear();
});

/*
|--------------------------------------------------------------------------
| 1. Batched BelongsTo display resolution
|--------------------------------------------------------------------------
*/

test('posts-table BelongsTo column resolves related models in a single query', function () {
    $model = new PreloadBelongsToPost;
    Aura::fake();
    Aura::setModel($model);
    Aura::registerRoutes('preload-author');
    Aura::clearRoutes();

    $authors = collect(range(1, 30))->map(fn ($i) => PreloadAuthor::create(['name' => 'Author '.$i]));

    $authors->each(function ($author) {
        PreloadBelongsToPost::create(['author_id' => $author->id]);
    });

    $component = livewire(Table::class, ['query' => null, 'model' => $model])->set('perPage', 100);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $component->call('$refresh');

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Exactly one SELECT against the related table for all 30 rows.
    expect(preloadSelectsFrom($queries, 'preload_authors'))->toBe(1);

    // Output still resolves each related title.
    $component->assertSee('Author 1');
    $component->assertSee('Author 30');
});

test('custom-table BelongsTo column resolves related models in a single query', function () {
    $model = new PreloadBelongsToProject;
    Aura::fake();
    Aura::setModel($model);
    Aura::registerRoutes('preload-author');
    Aura::clearRoutes();

    $authors = collect(range(1, 30))->map(fn ($i) => PreloadAuthor::create(['name' => 'Author '.$i]));

    $authors->each(function ($author, $i) {
        PreloadBelongsToProject::create(['name' => 'Project '.$i, 'author_id' => $author->id]);
    });

    $component = livewire(Table::class, ['query' => null, 'model' => $model])->set('perPage', 100);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $component->call('$refresh');

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect(preloadSelectsFrom($queries, 'preload_authors'))->toBe(1);

    $component->assertSee('Author 1');
    $component->assertSee('Author 30');
});

test('BelongsTo preload keeps team scope: cross-team related title stays absent', function () {
    $model = new PreloadBelongsToProject;
    Aura::fake();
    Aura::setModel($model);
    Aura::registerRoutes('preload-author');
    Aura::clearRoutes();

    // Same-team author, visible.
    $ownAuthor = PreloadAuthor::create(['name' => 'VisibleAuthor']);

    // Cross-team author: force a foreign team_id so TeamScope hides it.
    $foreignAuthor = PreloadAuthor::create(['name' => 'ForeignAuthor', 'team_id' => 999999]);

    PreloadBelongsToProject::create(['name' => 'P1', 'author_id' => $ownAuthor->id]);
    PreloadBelongsToProject::create(['name' => 'P2', 'author_id' => $foreignAuthor->id]);

    $component = livewire(Table::class, ['query' => null, 'model' => $model])->set('perPage', 100);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $component->call('$refresh');

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Still a single scoped query.
    expect(preloadSelectsFrom($queries, 'preload_authors'))->toBe(1);

    // The scoped-out (cross-team) title must not leak.
    $component->assertSee('VisibleAuthor');
    $component->assertDontSee('ForeignAuthor');
})->skip(fn () => ! config('aura.teams'), 'Teams disabled');

/*
|--------------------------------------------------------------------------
| 2. Eager-loaded relation fields (Tags)
|--------------------------------------------------------------------------
*/

test('Tags column eager-loads the relation once instead of per row', function () {
    $model = new PreloadTagPost;
    Aura::fake();
    Aura::setModel($model);

    $tagRed = Tag::create(['title' => 'RedTag', 'slug' => 'red-tag']);
    $tagBlue = Tag::create(['title' => 'BlueTag', 'slug' => 'blue-tag']);

    collect(range(1, 20))->each(function () use ($tagRed, $tagBlue) {
        PreloadTagPost::create(['tags' => [$tagRed->id, $tagBlue->id]]);
    });

    $component = livewire(Table::class, ['query' => null, 'model' => $model])->set('perPage', 100);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $component->call('$refresh');

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // The pivot table is touched exactly once (single eager load) for all rows.
    expect(preloadSelectsContaining($queries, 'post_relations'))->toBe(1);

    // Rendered HTML is unchanged: tag names still appear.
    $component->assertSee('RedTag');
    $component->assertSee('BlueTag');
});

/*
|--------------------------------------------------------------------------
| 3. Batched Image display resolution
|--------------------------------------------------------------------------
*/

test('Image column resolves attachments in a single query', function () {
    $model = new PreloadImagePost;
    Aura::fake();
    Aura::setModel($model);

    $attachments = collect(range(1, 20))->map(function ($i) {
        return Attachment::create([
            'name' => 'img'.$i,
            'url' => 'img'.$i.'.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    });

    $attachments->each(function ($attachment) {
        PreloadImagePost::create(['image' => [$attachment->id]]);
    });

    $component = livewire(Table::class, ['query' => null, 'model' => $model])->set('perPage', 100);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $component->call('$refresh');

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // The attachment batch is the only posts SELECT that filters by a set of
    // primary keys (whereKey). One query resolves attachments for all rows.
    $attachmentSelects = collect($queries)->filter(function ($q) {
        $sql = strtolower($q['query']);

        $fromPosts = str_contains($sql, 'from "posts"') || str_contains($sql, 'from `posts`');

        return $fromPosts && (str_contains($sql, '"posts"."id" in (') || str_contains($sql, '`posts`.`id` in ('));
    })->count();

    expect($attachmentSelects)->toBe(1);
});

/*
|--------------------------------------------------------------------------
| 4. Per-instance normalized-meta cache correctness
|--------------------------------------------------------------------------
*/

test('meta cache is invalidated on refresh so display reflects fresh meta', function () {
    Aura::fake();
    Aura::setModel(new PreloadMetaPost);

    $post = PreloadMetaPost::create(['subtitle' => 'original']);

    // Prime the fields + normalized-meta cache.
    expect($post->display('subtitle'))->toContain('original');

    // Mutate the meta row directly, bypassing the model.
    Meta::where('metable_id', $post->id)
        ->where('metable_type', PreloadMetaPost::class)
        ->where('key', 'subtitle')
        ->update(['value' => 'updated']);

    $post->refresh();

    expect($post->display('subtitle'))->toContain('updated');
});

test('meta cache is invalidated on load(meta) so display reflects fresh meta', function () {
    Aura::fake();
    Aura::setModel(new PreloadMetaPost);

    $post = PreloadMetaPost::create(['subtitle' => 'original']);

    expect($post->display('subtitle'))->toContain('original');

    Meta::where('metable_id', $post->id)
        ->where('metable_type', PreloadMetaPost::class)
        ->where('key', 'subtitle')
        ->update(['value' => 'reloaded']);

    $post->load('meta');

    expect($post->display('subtitle'))->toContain('reloaded');
});

<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $model = new TableRowsMemoizationModel;

    Aura::fake();
    Aura::setModel($model);

    $this->actingAs($this->user = createSuperAdmin());

    for ($i = 0; $i < 10; $i++) {
        TableRowsMemoizationModel::create([
            'title' => 'Test Post '.$i,
            'content' => 'Content '.$i,
            'type' => 'Post',
            'status' => 'publish',
        ]);
    }
});

class TableRowsMemoizationModel extends Resource
{
    public static $singularName = 'Post';

    public static ?string $slug = 'resource';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
            [
                'name' => 'Content',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'slug' => 'content',
                'validation' => '',
                'conditional_logic' => [],
            ],
        ];
    }
}

test('paginated rows query executes only once per render', function () {
    $post = TableRowsMemoizationModel::first();

    // Warm up: mount + first render so we only measure a clean re-render.
    $component = livewire(Table::class, ['query' => null, 'model' => $post]);

    DB::enableQueryLog();
    DB::flushQueryLog();

    // A single render (render() reads $this->rows, and the rowIds computed
    // property also reads $this->rows). Without memoization the paginated
    // SELECT runs twice; with #[Computed] memoization it runs once.
    $component->call('$refresh');

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // The paginated data fetch is the query selecting from `posts` with a
    // limit/offset pair (paginate() always adds an OFFSET). It must appear
    // exactly once for a single render; without memoization it runs twice.
    $paginatedSelects = collect($queries)->filter(function ($q) {
        $sql = strtolower($q['query']);

        $fromPosts = str_contains($sql, 'from "posts"') || str_contains($sql, 'from `posts`');

        return $fromPosts && str_contains($sql, 'offset');
    });

    expect($paginatedSelects->count())->toBe(1);
});

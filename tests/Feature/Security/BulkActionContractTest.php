<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Pest\Livewire\livewire;

class Core06BulkResource extends Resource
{
    public array $bulkActions = [
        'assignOwner' => [
            'ability' => 'update',
            'label' => 'Assign owner',
            'parameters' => [
                'owner_id' => [
                    'label' => 'Owner',
                    'options' => [
                        7 => 'Owner seven',
                        42 => 'Owner forty-two',
                    ],
                    'rules' => ['required', 'integer', 'min:1'],
                    'type' => 'integer',
                ],
                'tags' => [
                    'label' => 'Tags',
                    'rules' => ['nullable'],
                    'type' => 'array',
                ],
            ],
        ],
        'downloadCsv' => [
            'ability' => 'view',
            'download' => [
                'content_type' => 'text/csv',
                'filename' => 'core06-export.csv',
            ],
            'label' => 'Download CSV',
            'method' => 'collection',
            'parameters' => [
                'prefix' => [
                    'label' => 'Prefix',
                    'rules' => ['required', 'string', 'max:20'],
                    'type' => 'string',
                ],
            ],
        ],
        'invalidDownload' => [
            'ability' => 'view',
            'download' => [
                'content_type' => 'text/plain',
                'filename' => 'invalid.txt',
            ],
            'label' => 'Invalid download',
            'method' => 'collection',
        ],
        'invalidTypedDownload' => [
            'ability' => 'view',
            'download' => [
                'content_type' => 'text/plain',
                'filename' => 'invalid-typed.txt',
            ],
            'label' => 'Invalid typed download',
            'method' => 'collection',
        ],
        'invalidParameterMapDownload' => [
            'ability' => 'view',
            'download' => [
                'content_type' => 'text/plain',
                'filename' => 'invalid-parameters.txt',
            ],
            'label' => 'Invalid parameter map download',
            'method' => 'collection',
            'parameters' => [
                'prefix' => [
                    'label' => 'Prefix',
                    'rules' => ['required', 'string'],
                    'type' => 'string',
                ],
            ],
        ],
        'untypedDownload' => [
            'ability' => 'view',
            'download' => [
                'content_type' => 'text/plain',
                'filename' => 'untyped.txt',
            ],
            'label' => 'Untyped download',
            'method' => 'collection',
        ],
        'smallDownload' => [
            'ability' => 'view',
            'label' => 'Small download',
            'method' => 'collection',
        ],
    ];

    /** @var list<list<int|string>> */
    public static array $downloadChunks = [];

    public static $singularName = 'Core06 bulk resource';

    public static ?string $slug = 'core06-bulk-resource';

    public static string $type = 'Core06BulkResource';

    public function assignOwner(array $parameters): void
    {
        $this->content = get_debug_type($parameters['owner_id']).':'.$parameters['owner_id'];
        $this->save();
    }

    public function downloadCsv(array $ids, array $parameters): string
    {
        self::$downloadChunks[] = $ids;

        return collect($ids)
            ->map(fn (int|string $id): string => $parameters['prefix'].','.$id."\n")
            ->implode('');
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'searchable' => true,
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Duplicate rank',
                'slug' => 'duplicate_rank',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }

    public function indexQuery(Builder $query, ?Table $table = null): Builder
    {
        return $table?->parent ? $query->whereKey($table->parent->getKey()) : $query;
    }

    public function invalidDownload(): string
    {
        return 'must not run';
    }

    public function invalidParameterMapDownload(array $ids, string $parameters): string
    {
        return implode(',', $ids).$parameters;
    }

    public function invalidTypedDownload(string $ids): string
    {
        return $ids;
    }

    public function smallDownload(array $ids): StreamedResponse
    {
        return response()->streamDownload(
            static fn () => print implode("\n", $ids)."\n",
            'small.txt',
        );
    }

    public function sort_duplicate_rank(Builder $query, string $direction): void
    {
        $duplicates = DB::query()
            ->selectRaw('1 as duplicate_marker')
            ->unionAll(DB::query()->selectRaw('2 as duplicate_marker'));

        $query->crossJoinSub($duplicates, 'core06_sorted_duplicates')
            ->orderBy('duplicate_marker', $direction)
            ->orderByRaw('CASE WHEN posts.title = ? THEN 0 ELSE 1 END', ['Bravo'])
            ->orderBy($this->qualifyColumn('title'), 'desc');
    }

    public function untypedDownload($ids): string
    {
        return implode(',', $ids);
    }
}

class Core06BulkResourcePolicy
{
    public function view(User $user, Core06BulkResource $resource): bool
    {
        return $user->exists && $resource->title !== 'Denied export';
    }
}

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([Core06BulkResource::class]);
    Aura::setModel(new Core06BulkResource);
    Cache::clear();
    Core06BulkResource::$downloadChunks = [];

    $this->actingAs(createSuperAdmin());
});

test('download actions redirect Livewire to a signed HTTP stream and clear selection state', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    config()->set('aura.security.bulk_downloads.chunk_size', 20);
    config()->set('aura.security.bulk_downloads.max_records', 500);
    $resources = collect(range(1, 75))->map(fn (int $number) => Core06BulkResource::create([
        'title' => 'Export match '.$number,
    ]));
    Core06BulkResource::create(['title' => 'Outside selection']);
    $excluded = $resources->get(10);

    $component = livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('search', 'Export match')
        ->call('selectAllRows')
        ->set('selectAllExclusions', [$excluded->getKey()])
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'resource'])
        ->assertRedirect()
        ->assertSet('selected', [])
        ->assertSet('selectAll', false)
        ->assertSet('selectAllExclusions', []);

    $url = $component->effects['redirect'];

    expect(URL::hasValidSignature(request()->create($url)))->toBeTrue();

    $response = $this->get($url)
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload('core06-export.csv');
    $lines = collect(explode("\n", trim($response->streamedContent())));

    expect($lines)->toHaveCount(74)
        ->not->toContain('resource,'.$excluded->getKey())
        ->and(Core06BulkResource::$downloadChunks)->toHaveCount(4)
        ->and(collect(Core06BulkResource::$downloadChunks)->map(fn (array $chunk): int => count($chunk))->all())
        ->toBe([20, 20, 20, 14]);
});

test('large download scopes de-duplicate in SQL before bounded streaming', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    config()->set('aura.security.bulk_downloads.chunk_size', 25);
    $resources = collect(range(1, 60))->map(fn (int $number) => Core06BulkResource::create([
        'title' => 'Duplicate export '.$number,
    ]));
    $queryHash = DynamicFunctions::add(function (): Builder {
        $duplicates = DB::query()
            ->selectRaw('1 as duplicate_marker')
            ->unionAll(DB::query()->selectRaw('2 as duplicate_marker'));

        return Core06BulkResource::query()->crossJoinSub($duplicates, 'core06_download_duplicates');
    });
    $component = livewire(Table::class, ['query' => $queryHash, 'model' => new Core06BulkResource])
        ->call('selectAllRows')
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'deduplicated'])
        ->assertRedirect();

    $lines = collect(explode("\n", trim(
        $this->get($component->effects['redirect'])->assertSuccessful()->streamedContent()
    )));

    expect($lines)->toHaveCount($resources->count())
        ->and($lines->unique())->toHaveCount($resources->count())
        ->and(collect(Core06BulkResource::$downloadChunks)->map(fn (array $chunk): int => count($chunk))->all())
        ->toBe([25, 25, 10]);
});

test('signed downloads preserve custom display sorting while de-duplicating non-adjacent joins', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    collect(['Alpha', 'Charlie', 'Bravo'])->each(fn (string $title) => Core06BulkResource::create([
        'title' => $title,
    ]));

    $component = livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('sorts', ['duplicate_rank' => 'desc'])
        ->call('selectAllRows')
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'ordered'])
        ->assertRedirect();

    $ids = collect(explode("\n", trim(
        $this->get($component->effects['redirect'])->assertSuccessful()->streamedContent()
    )))->map(fn (string $line): int => (int) (string) str($line)->after(','));
    $titles = Core06BulkResource::query()->whereKey($ids)->pluck('title', 'id');

    expect($ids)->toHaveCount(3)
        ->and($ids->unique())->toHaveCount(3)
        ->and($ids->map(fn (int $id): string => $titles[$id])->all())
        ->toBe(['Bravo', 'Charlie', 'Alpha']);
});

test('signed downloads authorize in bounded queries without reloading the receiver', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    config()->set('aura.security.bulk_downloads.chunk_size', 20);
    $resources = collect(range(1, 45))->map(fn (int $number) => Core06BulkResource::create([
        'title' => 'Bounded export '.$number,
    ]));
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    $authorizationQueryCount = function () use (&$queries): int {
        return collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'select "posts".* from "posts"')
            && str_contains($sql, '"posts"."id" in ('))->count();
    };

    $component = livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', $resources->pluck('id')->all())
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'bounded'])
        ->assertRedirect();

    expect($authorizationQueryCount())->toBe(3)
        ->and(Core06BulkResource::$downloadChunks)->toBe([]);

    $response = $this->get($component->effects['redirect'])->assertSuccessful();

    expect($authorizationQueryCount())->toBe(6)
        ->and(Core06BulkResource::$downloadChunks)->toBe([]);

    $response->streamedContent();

    expect($authorizationQueryCount())->toBe(9)
        ->and(Core06BulkResource::$downloadChunks)->toHaveCount(3);
});

test('bulk download URLs reject tampering and expiration', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    $resource = Core06BulkResource::create(['title' => 'Export match']);
    $component = livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'resource'])
        ->assertRedirect();
    $url = $component->effects['redirect'];

    $this->get($url.'&forged=1')->assertForbidden();

    $this->travel(3)->minutes();

    $this->get($url)->assertForbidden();
    expect(Core06BulkResource::$downloadChunks)->toBe([]);
});

test('bulk downloads reject forged parameters, empty scopes, and denied rows before issuing a URL', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    $allowed = Core06BulkResource::create(['title' => 'Allowed export']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$allowed->getKey()])
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'ok', 'forged' => true])
        ->assertHasErrors(['parameters'])
        ->assertNoRedirect();

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('search', 'does-not-exist')
        ->call('selectAllRows')
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'ok'])
        ->assertHasErrors(['selected'])
        ->assertNoRedirect();

    $this->actingAs(createAdmin());
    Gate::policy(Core06BulkResource::class, Core06BulkResourcePolicy::class);
    $denied = Core06BulkResource::create(['title' => 'Denied export']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$allowed->getKey(), $denied->getKey()])
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'ok'])
        ->assertForbidden()
        ->assertNoRedirect();

    expect(Core06BulkResource::$downloadChunks)->toBe([]);
});

test('bulk downloads reject invalid handler signatures before issuing a URL', function (
    string $action,
    array $parameters,
) {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    $resource = Core06BulkResource::create(['title' => 'Invalid handler']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', $action, $parameters)
        ->assertStatus(422)
        ->assertNoRedirect();
})->with([
    'wrong arity' => ['invalidDownload', []],
    'scalar ids' => ['invalidTypedDownload', []],
    'scalar parameter map' => ['invalidParameterMapDownload', ['prefix' => 'test']],
    'untyped ids' => ['untypedDownload', []],
]);

test('a different user cannot consume a bulk download URL owned by its issuer', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    $owner = auth()->user();
    $resource = Core06BulkResource::create(['title' => 'Owner export']);
    $component = livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'owner'])
        ->assertRedirect();
    $url = $component->effects['redirect'];

    $this->actingAs(User::factory()->create());
    $this->get($url)->assertUnprocessable();

    $this->actingAs($owner);
    $this->get($url)->assertSuccessful()->streamedContent();
    $this->get($url)->assertUnprocessable();
});

test('a bulk download URL remains bound to the issuing team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team binding is only applicable when teams are enabled.');
    }

    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    $owner = auth()->user();
    $issuingTeamId = $owner->current_team_id;
    $resource = Core06BulkResource::create(['title' => 'Team-bound export']);
    $component = livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'team'])
        ->assertRedirect();
    $url = $component->effects['redirect'];
    $otherTeam = Team::factory()->create();

    $owner->forceFill(['current_team_id' => $otherTeam->getKey()])->save();
    $this->get($url)->assertUnprocessable();

    $owner->forceFill(['current_team_id' => $issuingTeamId])->save();
    $this->get($url)->assertSuccessful()->streamedContent();
});

test('small Livewire downloads clear selection before returning the buffered response', function () {
    $resource = Core06BulkResource::create(['title' => 'Small export']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'smallDownload')
        ->assertFileDownloaded('small.txt', $resource->getKey()."\n")
        ->assertSet('selected', [])
        ->assertSet('selectAll', false);
});

test('small Livewire downloads concatenate every bounded handler chunk', function () {
    config()->set('aura.security.table_mutations.chunk_size', 100);
    $resources = collect(range(1, 125))->map(fn (int $number) => Core06BulkResource::create([
        'title' => 'Small export '.$number,
    ]));

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', $resources->pluck('id')->all())
        ->call('bulkCollectionAction', 'smallDownload')
        ->assertFileDownloaded('small.txt', $resources->pluck('id')->reverse()->implode("\n")."\n")
        ->assertSet('selected', [])
        ->assertSet('selectAll', false);
});

test('declared bulk parameters are validated, typed, and passed to a record action', function () {
    $resource = Core06BulkResource::create(['title' => 'Target']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'assignOwner', ['owner_id' => '42', 'tags' => ['priority']])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('int:42');
});

test('the bulk action menu renders declared parameter inputs without public component state', function () {
    Core06BulkResource::create(['title' => 'Target']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->assertSee('Assign owner')
        ->assertSeeHtml('x-model="parameters.owner_id"')
        ->assertSeeHtml('value="42"')
        ->assertDontSeeHtml('value="Owner forty-two"')
        ->assertSeeHtml('x-model="arrayInputs.tags"')
        ->assertSeeHtml('<textarea');
});

test('the selected rows query is the exact filtered and searched display query', function () {
    $matching = Core06BulkResource::create(['title' => 'Exact search match']);
    Core06BulkResource::create(['title' => 'Outside display scope']);
    $component = livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('search', 'Exact search')
        ->call('selectAllRows');

    expect($component->instance()->getSelectedRowsQueryProperty()->pluck('id')->all())
        ->toBe([$matching->getKey()]);
});

test('select all stays inside the exact parent scope', function () {
    config()->set('aura.security.bulk_downloads.cache_store', 'file');
    $parent = Core06BulkResource::create(['title' => 'Parent scope']);
    $outside = Core06BulkResource::create(['title' => 'Outside parent scope']);
    $component = livewire(Table::class, [
        'model' => new Core06BulkResource,
        'parent' => $parent,
        'query' => null,
    ])
        ->call('selectAllRows')
        ->call('bulkCollectionAction', 'downloadCsv', ['prefix' => 'parent'])
        ->assertRedirect();

    $content = $this->get($component->effects['redirect'])
        ->assertSuccessful()
        ->streamedContent();

    expect($content)->toContain('parent,'.$parent->getKey())
        ->not->toContain('parent,'.$outside->getKey());
});

test('bulk actions reject forged and invalid parameters before invoking a handler', function (array $parameters) {
    $resource = Core06BulkResource::create([
        'title' => 'Target',
        'content' => 'unchanged',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'assignOwner', $parameters)
        ->assertHasErrors();

    expect($resource->fresh()->content)->toBe('unchanged');
})->with([
    'undeclared key' => [['owner_id' => 42, 'is_admin' => true]],
    'value outside declared options' => [['owner_id' => 8]],
    'wrong type' => [['owner_id' => 'not-an-integer']],
    'missing required value' => [[]],
]);

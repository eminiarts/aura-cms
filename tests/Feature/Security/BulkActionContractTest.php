<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Cache;
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
                    'rules' => ['required', 'integer', 'min:1'],
                    'type' => 'integer',
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
        ];
    }

    public function smallDownload(array $ids): StreamedResponse
    {
        return response()->streamDownload(
            static fn () => print implode(',', $ids),
            'small.txt',
        );
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

test('small Livewire downloads clear selection before returning the buffered response', function () {
    $resource = Core06BulkResource::create(['title' => 'Small export']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'smallDownload')
        ->assertFileDownloaded('small.txt', (string) $resource->getKey())
        ->assertSet('selected', [])
        ->assertSet('selectAll', false);
});

test('declared bulk parameters are validated, typed, and passed to a record action', function () {
    $resource = Core06BulkResource::create(['title' => 'Target']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'assignOwner', ['owner_id' => '42'])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('int:42');
});

test('the bulk action menu renders declared parameter inputs without public component state', function () {
    Core06BulkResource::create(['title' => 'Target']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->assertSee('Assign owner')
        ->assertSeeHtml('x-model="parameters.owner_id"')
        ->assertSeeHtml('type="number"');
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
    'wrong type' => [['owner_id' => 'not-an-integer']],
    'missing required value' => [[]],
]);

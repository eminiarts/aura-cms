<?php

use Aura\Base\Contracts\DeclaresTableParentScopes;
use Aura\Base\Contracts\TableColumnCapabilityResolver;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Table\FilterGroupStateMutator;
use Aura\Base\Table\TableColumnCapability;
use Aura\Base\Table\TableParentScope;
use Aura\Base\Table\TableQueryState;
use Aura\Base\Table\TableQueryStateApplier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Core22QueryResource);
});

class Core22QueryResource extends Resource implements DeclaresTableParentScopes
{
    public static ?string $slug = 'core-22-query-resource';

    public static string $type = 'Core22QueryResource';

    protected static array $searchable = ['title', 'summary'];

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Summary', 'slug' => 'summary', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Score', 'slug' => 'score', 'type' => 'Aura\\Base\\Fields\\Number'],
        ];
    }

    public function tableParentScopes(): array
    {
        return [
            TableParentScope::foreignKey(
                key: 'parent',
                parentResource: self::class,
                foreignKey: 'parent_id',
            ),
        ];
    }
}

test('query state has a canonical versioned query string round trip and removable parent state', function () {
    $state = TableQueryState::fromArray([
        'v' => 1,
        'sorts' => [['key' => 'score', 'direction' => 'DESC']],
        'parent' => ['scope' => 'parent', 'id' => '42'],
        'search' => 'needle',
        'filters' => [[
            'operator' => 'and',
            'filters' => [[
                'name' => 'summary',
                'operator' => 'contains',
                'value' => 'blue',
                'main_operator' => 'and',
            ]],
        ]],
    ]);

    $encoded = $state->toQueryString();
    $roundTrip = TableQueryState::fromQueryString($encoded);

    expect($roundTrip->toArray())->toBe($state->toArray())
        ->and($roundTrip->toQueryString())->toBe($encoded)
        ->and($roundTrip->withoutParentScope()->toArray())->not->toHaveKey('parent');
});

test('query state rejects unsupported versions and unknown serialized keys', function (array $payload) {
    expect(fn () => TableQueryState::fromArray($payload))->toThrow(InvalidArgumentException::class);
})->with([
    'unsupported version' => [['v' => 2]],
    'unknown key' => [['v' => 1, 'column' => 'team_id']],
    'arbitrary parent column' => [['v' => 1, 'parent' => ['scope' => 'parent', 'id' => 1, 'column' => 'team_id']]],
]);

test('direct query applier preserves physical meta search sort and table parity', function () {
    $parent = Core22QueryResource::create([
        'title' => 'Parent',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
    ]);
    $matching = Core22QueryResource::create([
        'title' => 'Needle match',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
        'parent_id' => $parent->getKey(),
        'summary' => 'blue sky',
        'score' => 20,
    ]);
    Core22QueryResource::create([
        'title' => 'Needle excluded',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
        'parent_id' => $parent->getKey(),
        'summary' => 'red sky',
        'score' => 30,
    ]);
    Core22QueryResource::create([
        'title' => 'Needle wrong parent',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
        'summary' => 'blue sky',
        'score' => 40,
    ]);

    $state = TableQueryState::fromArray([
        'v' => 1,
        'filters' => [[
            'operator' => 'and',
            'filters' => [[
                'name' => 'summary',
                'operator' => 'contains',
                'value' => 'blue',
            ], [
                'name' => 'score',
                'operator' => 'greater_than',
                'value' => 10,
                'main_operator' => 'and',
            ]],
        ]],
        'search' => 'Needle',
        'sorts' => [['key' => 'score', 'direction' => 'desc']],
        'parent' => ['scope' => 'parent', 'id' => $parent->getKey()],
    ]);

    $directIds = (new TableQueryStateApplier)->apply(
        Core22QueryResource::query(),
        new Core22QueryResource,
        $state,
    )->pluck('id')->all();

    expect($directIds)->toBe([$matching->getKey()]);

    livewire(Table::class, [
        'model' => new Core22QueryResource,
        'query' => null,
        'tableState' => $state->toQueryString(),
    ])->assertViewHas('rows', fn ($rows): bool => $rows->pluck('id')->all() === $directIds);
});

test('trusted server-owned computed capabilities apply callbacks and unknown keys fail closed', function () {
    $low = Core22QueryResource::create([
        'title' => 'Low value',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
        'score' => 5,
    ]);
    $high = Core22QueryResource::create([
        'title' => 'High match',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
        'score' => 50,
    ]);
    $resolver = new class implements TableColumnCapabilityResolver
    {
        public function resolve(Resource $resource, string $key): ?TableColumnCapability
        {
            if ($key !== 'weighted_score') {
                return null;
            }

            return TableColumnCapability::computed(
                key: 'weighted_score',
                operators: ['greater_than'],
                validateFilter: static fn (array $filter): bool => is_string($filter['value'] ?? null),
                applyFilter: function (Builder $query, Resource $resource, array $filter): void {
                    $query->where($resource->qualifyColumn('title'), 'like', '%'.$filter['value'].'%');
                },
                applySort: function (Builder $query, Resource $resource, string $direction): void {
                    $query->orderBy($resource->qualifyColumn('title'), $direction);
                },
            );
        }
    };
    $applier = new TableQueryStateApplier($resolver);

    $trusted = TableQueryState::fromArray([
        'v' => 1,
        'filters' => [['filters' => [[
            'name' => 'weighted_score',
            'operator' => 'greater_than',
            'value' => 'match',
        ]]]],
        'sorts' => [['key' => 'weighted_score', 'direction' => 'desc']],
    ]);
    $forged = TableQueryState::fromArray([
        'v' => 1,
        'sorts' => [['key' => 'score desc; drop table posts', 'direction' => 'asc']],
    ]);

    expect($applier->apply(Core22QueryResource::query(), new Core22QueryResource, $trusted)->pluck('id')->all())
        ->toBe([$high->getKey()])
        ->and($applier->apply(Core22QueryResource::query(), new Core22QueryResource, $forged)->pluck('id')->all())
        ->toBe([])
        ->and(Core22QueryResource::find($low->getKey()))->not->toBeNull();
});

test('serialized state scopes select all to the same effective query', function () {
    $matching = Core22QueryResource::create([
        'title' => 'Selected match',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
        'summary' => 'blue',
    ]);
    Core22QueryResource::create([
        'title' => 'Excluded',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
        'summary' => 'red',
    ]);
    $state = TableQueryState::fromArray([
        'v' => 1,
        'filters' => [['filters' => [[
            'name' => 'summary',
            'operator' => 'is',
            'value' => 'blue',
        ]]]],
    ]);
    $component = livewire(Table::class, [
        'model' => new Core22QueryResource,
        'query' => null,
        'tableState' => $state->toQueryString(),
    ])->call('selectAllRows');

    $rowIds = $component->instance()->rowsQuery()->pluck('id')->all();
    $selectedIds = $component->instance()->getSelectedRowsQueryProperty()->pluck('id')->all();

    expect($rowIds)->toBe([$matching->getKey()])
        ->and($selectedIds)->toBe($rowIds);
});

test('unknown and cross-team parent scopes fail closed', function () {
    $unknown = TableQueryState::fromArray([
        'v' => 1,
        'parent' => ['scope' => 'forged', 'id' => 1],
    ]);

    expect((new TableQueryStateApplier)->apply(
        Core22QueryResource::query(),
        new Core22QueryResource,
        $unknown,
    )->pluck('id')->all())->toBe([]);

    if (! config('aura.teams')) {
        return;
    }

    $otherTeam = foreignTeam();
    $foreignParent = Core22QueryResource::createForTeamForSystem($otherTeam->getKey(), [
        'title' => 'Foreign parent',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
    ]);
    $crossTeam = TableQueryState::fromArray([
        'v' => 1,
        'parent' => ['scope' => 'parent', 'id' => $foreignParent->getKey()],
    ]);

    expect(fn () => (new TableQueryStateApplier)->apply(
        Core22QueryResource::query(),
        new Core22QueryResource,
        $crossTeam,
    ))->toThrow(ModelNotFoundException::class);
});

test('parent resolution authorizes the declared parent', function () {
    $parent = Core22QueryResource::create([
        'title' => 'Protected parent',
        'type' => Core22QueryResource::$type,
        'status' => 'publish',
    ]);
    $state = TableQueryState::fromArray([
        'v' => 1,
        'parent' => ['scope' => 'parent', 'id' => $parent->getKey()],
    ]);
    $limitedUser = createAdmin();
    $this->actingAs($limitedUser);

    expect(fn () => (new TableQueryStateApplier)->apply(
        Core22QueryResource::query(),
        new Core22QueryResource,
        $state,
    ))->toThrow(AuthorizationException::class);
});

test('filter group mutations are composable without saved-filter modal state', function () {
    $mutator = new FilterGroupStateMutator;
    $filters = [];
    $filters = $mutator->addGroup($filters, [
        'name' => 'title',
        'operator' => 'contains',
        'value' => null,
    ]);
    $filters = $mutator->addFilter($filters, 0, [
        'name' => 'score',
        'operator' => 'greater_than',
        'value' => 5,
    ]);
    $filters = $mutator->removeFilter($filters, 0, 0);

    expect($filters)->toBe([[
        'filters' => [[
            'name' => 'score',
            'operator' => 'greater_than',
            'value' => 5,
        ]],
    ]]);
});

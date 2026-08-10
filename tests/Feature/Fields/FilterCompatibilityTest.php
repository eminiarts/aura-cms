<?php

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Contracts\ProvidesFilterCapability;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Checkbox;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Tags;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Tag;
use Illuminate\Database\Eloquent\Builder;

use function Pest\Livewire\livewire;

class LegacyFilterCompatibilityResource extends Resource
{
    public static ?string $slug = 'legacy-filter-compatibility-resource';

    public static string $type = 'LegacyFilterCompatibilityResource';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
            ],
            [
                'name' => 'Stage',
                'slug' => 'stage',
                'type' => Select::class,
                'options' => [
                    'draft' => 'Draft',
                    'published' => 'Published',
                ],
            ],
            [
                'name' => 'Summary',
                'slug' => 'summary',
                'type' => Text::class,
            ],
            [
                'name' => 'Topics',
                'slug' => 'topics',
                'type' => Tags::class,
                'resource' => Tag::class,
                'create' => false,
            ],
            [
                'name' => 'Segments',
                'slug' => 'segments',
                'type' => Checkbox::class,
                'options' => [
                    ['key' => 1, 'value' => 'One'],
                    ['key' => 2, 'value' => 'Two'],
                ],
            ],
            [
                'name' => 'Reviewed on',
                'slug' => 'reviewed_on',
                'type' => Date::class,
                'format' => 'Y-m-d',
            ],
            [
                'name' => 'Priority',
                'slug' => 'priority',
                'type' => LegacyPackagePriorityField::class,
            ],
        ];
    }
}

class LegacyParentScopedFilterCompatibilityResource extends LegacyFilterCompatibilityResource
{
    public static ?string $slug = 'legacy-parent-scoped-filter-compatibility-resource';

    public static string $type = 'LegacyParentScopedFilterCompatibilityResource';

    public function indexQuery(Builder $query, ?Table $table = null): Builder
    {
        return $table?->parent ? $query->whereKey($table->parent->getKey()) : $query;
    }
}

class LegacyPackagePriorityField extends Field implements ProvidesFilterCapability
{
    public function provideAuraFilterCapability(Resource $model, array $field): FilterCapability
    {
        return FilterCapability::custom(
            component: 'aura::fields.filters.text',
            operators: ['is' => 'is'],
            queryHandler: LegacyPackagePriorityFilter::class,
            values: [
                'urgent' => 'Urgent',
                'routine' => 'Routine',
            ],
        );
    }
}

class LegacyPackagePriorityFilter implements AppliesFieldFilter
{
    public static int $applyCount = 0;

    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        array $filter,
        FilterCapability $capability,
    ): void {
        self::$applyCount++;

        $prefix = $filter['value'] === 'urgent' ? '[P1]%' : '[Routine]%';
        $query->where($query->getModel()->qualifyColumn('title'), 'like', $prefix);
    }
}

abstract class LegacyFilterHookTable extends Table
{
    public int $legacyHookCalls = 0;

    protected function markLegacyHookCalled(Builder $query): void
    {
        $this->legacyHookCalls++;
        $query->whereRaw('1 = 0');
    }
}

class LegacyApplyFilterBasedOnTypeTable extends LegacyFilterHookTable
{
    protected function applyFilterBasedOnType(Builder $query, array $filter): void
    {
        parent::applyFilterBasedOnType($query, $filter);
        $this->markLegacyHookCalled($query);
    }
}

class LegacyScopedApplyFilterBasedOnTypeTable extends LegacyFilterHookTable
{
    protected function applyFilterBasedOnType(Builder $query, array $filter): void
    {
        $this->legacyHookCalls++;
        parent::applyFilterBasedOnType($query, $filter);
    }
}

class LegacyApplyTableFieldFilterTable extends LegacyFilterHookTable
{
    protected function applyTableFieldFilter(Builder $query, array $filter): Builder
    {
        parent::applyTableFieldFilter($query, $filter);
        $this->markLegacyHookCalled($query);

        return $query;
    }
}

class LegacyApplyMetaFieldFilterTable extends LegacyFilterHookTable
{
    protected function applyMetaFieldFilter(Builder $query, array $filter): Builder
    {
        parent::applyMetaFieldFilter($query, $filter);
        $this->markLegacyHookCalled($query);

        return $query;
    }
}

class LegacyApplyStandardMetaFilterTable extends LegacyFilterHookTable
{
    protected function applyStandardMetaFilter(Builder $query, array $filter): void
    {
        parent::applyStandardMetaFilter($query, $filter);
        $this->markLegacyHookCalled($query);
    }
}

class LegacyApplyOperatorConditionTable extends LegacyFilterHookTable
{
    protected function applyOperatorCondition(Builder $query, array $filter): void
    {
        parent::applyOperatorCondition($query, $filter);
        $this->markLegacyHookCalled($query);
    }
}

class LegacyPassThroughOperatorConditionTable extends LegacyFilterHookTable
{
    protected function applyOperatorCondition(Builder $query, array $filter): void
    {
        $this->legacyHookCalls++;
        parent::applyOperatorCondition($query, $filter);
    }
}

class LegacyApplyIsEmptyMetaFilterTable extends LegacyFilterHookTable
{
    protected function applyIsEmptyMetaFilter(Builder $query, array $filter): void
    {
        parent::applyIsEmptyMetaFilter($query, $filter);
        $this->markLegacyHookCalled($query);
    }
}

class LegacyApplyIsNotEmptyMetaFilterTable extends LegacyFilterHookTable
{
    protected function applyIsNotEmptyMetaFilter(Builder $query, array $filter): void
    {
        parent::applyIsNotEmptyMetaFilter($query, $filter);
        $this->markLegacyHookCalled($query);
    }
}

class LegacyApplyRelationFieldFilterTable extends LegacyFilterHookTable
{
    protected function applyRelationFieldFilter(Builder $query, array $filter): void
    {
        parent::applyRelationFieldFilter($query, $filter);
        $this->markLegacyHookCalled($query);
    }
}

class LegacyIsRelationBackedFilterTable extends LegacyFilterHookTable
{
    protected function applyRelationFieldFilter(Builder $query, array $filter): void
    {
        parent::applyRelationFieldFilter($query, $filter);
        $query->whereRaw('1 = 0');
    }

    protected function isRelationBackedFilter(array $filter): bool
    {
        $this->legacyHookCalls++;

        return parent::isRelationBackedFilter($filter);
    }
}

class LegacyStandardRelationFallbackTable extends LegacyFilterHookTable
{
    protected function applyRelationFieldFilter(Builder $query, array $filter): void
    {
        parent::applyRelationFieldFilter($query, $filter);
        $this->markLegacyHookCalled($query);
    }

    protected function isRelationBackedFilter(array $filter): bool
    {
        return false;
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new LegacyFilterCompatibilityResource);
    LegacyPackagePriorityFilter::$applyCount = 0;
});

test('legacy table and meta query filter overrides remain reachable from rowsQuery', function (string $component, array $filter) {
    $summary = $filter['operator'] === 'is_empty' ? null : 'needle';
    $matching = LegacyFilterCompatibilityResource::create([
        'type' => LegacyFilterCompatibilityResource::$type,
        'title' => 'Matching record',
        'stage' => 'draft',
        'summary' => $summary,
    ]);

    livewire(Table::class, ['model' => $matching])
        ->set('filters.custom', [['filters' => [$filter]]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id]);

    $test = livewire($component, ['model' => $matching])
        ->set('filters.custom', [['filters' => [$filter]]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());

    expect($test->instance()->legacyHookCalls)->toBeGreaterThan(0);
})->with([
    'type dispatch' => [LegacyApplyFilterBasedOnTypeTable::class, ['name' => 'stage', 'operator' => 'is', 'value' => 'draft']],
    'table field' => [LegacyApplyTableFieldFilterTable::class, ['name' => 'title', 'operator' => 'contains', 'value' => 'Matching']],
    'meta field' => [LegacyApplyMetaFieldFilterTable::class, ['name' => 'summary', 'operator' => 'contains', 'value' => 'needle']],
    'standard meta field' => [LegacyApplyStandardMetaFilterTable::class, ['name' => 'summary', 'operator' => 'contains', 'value' => 'needle']],
    'meta operator' => [LegacyApplyOperatorConditionTable::class, ['name' => 'summary', 'operator' => 'contains', 'value' => 'needle']],
    'empty meta field' => [LegacyApplyIsEmptyMetaFilterTable::class, ['name' => 'summary', 'operator' => 'is_empty', 'value' => null]],
    'nonempty meta field' => [LegacyApplyIsNotEmptyMetaFilterTable::class, ['name' => 'summary', 'operator' => 'is_not_empty', 'value' => null]],
]);

test('legacy relationship query filter overrides remain reachable from rowsQuery', function (string $component) {
    $tag = Tag::create(['title' => 'Compatibility tag']);
    $matching = LegacyFilterCompatibilityResource::create([
        'type' => LegacyFilterCompatibilityResource::$type,
        'title' => 'Related record',
        'topics' => [$tag->id],
    ]);

    livewire(Table::class, ['model' => $matching])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'topics',
                'operator' => 'contains',
                'value' => [$tag->id],
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id]);

    $test = livewire($component, ['model' => $matching])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'topics',
                'operator' => 'contains',
                'value' => [$tag->id],
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());

    expect($test->instance()->legacyHookCalls)->toBeGreaterThan(0);
})->with([
    'relation field' => [LegacyApplyRelationFieldFilterTable::class],
    'relation detection' => [LegacyIsRelationBackedFilterTable::class],
]);

test('legacy saved relationship options still reach the standard meta relation fallback', function () {
    $tag = Tag::create(['title' => 'Saved compatibility tag']);
    $matching = LegacyFilterCompatibilityResource::create([
        'type' => LegacyFilterCompatibilityResource::$type,
        'title' => 'Saved related record',
        'topics' => [$tag->id],
    ]);
    $filter = [
        'name' => 'topics',
        'operator' => 'contains',
        'value' => [$tag->id],
        'options' => ['resource_type' => Tag::class],
    ];

    livewire(Table::class, ['model' => $matching])
        ->set('filters.custom', [['filters' => [$filter]]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id]);

    $test = livewire(LegacyStandardRelationFallbackTable::class, ['model' => $matching])
        ->set('filters.custom', [['filters' => [$filter]]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());

    expect($test->instance()->legacyHookCalls)->toBeGreaterThan(0);
});

test('legacy operator parent behavior retains date empty semantics', function (string $operator, ?string $reviewedOn) {
    $matching = LegacyFilterCompatibilityResource::create([
        'type' => LegacyFilterCompatibilityResource::$type,
        'title' => 'Legacy date empty record',
        'reviewed_on' => $reviewedOn,
    ]);

    $test = livewire(LegacyPassThroughOperatorConditionTable::class, ['model' => $matching])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'reviewed_on',
                'operator' => $operator,
                'value' => null,
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id]);

    expect($test->instance()->legacyHookCalls)->toBeGreaterThan(0);
})->with([
    'date empty' => ['date_is_empty', null],
    'date not empty' => ['date_is_not_empty', '2026-08-10'],
]);

test('legacy computed filter descriptor remains additive and source compatible', function () {
    $record = LegacyFilterCompatibilityResource::create([
        'type' => LegacyFilterCompatibilityResource::$type,
        'title' => 'Descriptor record',
    ]);

    $stage = livewire(Table::class, ['model' => $record])->instance()->fieldsForFilter()['stage'];

    expect($stage)
        ->toHaveKey('type', 'Select')
        ->toHaveKey('filterOptions')
        ->toHaveKey('filterValues', ['draft' => 'Draft', 'published' => 'Published'])
        ->toHaveKey('canonicalFilterValues')
        ->toHaveKey('filter.values');

    expect($stage['canonicalFilterValues'])->toBe($stage['filter']['values']);
});

test('legacy updatedFiltersCustom hook delegates to the current lifecycle behavior', function () {
    $record = LegacyFilterCompatibilityResource::create([
        'type' => LegacyFilterCompatibilityResource::$type,
        'title' => 'Lifecycle record',
    ]);

    $test = livewire(Table::class, ['model' => $record])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'summary',
                'operator' => 'contains',
                'value' => 'stale',
                'options' => [],
            ]],
        ]]);

    $test->instance()->updatedFiltersCustom('stage', 'custom.0.filters.0.name');

    expect($test->instance()->filters['custom'][0]['filters'][0])
        ->toMatchArray([
            'name' => 'summary',
            'operator' => 'is',
            'value' => null,
        ]);
});

test('legacy dispatch preserves parent and tenant scopes and applies custom capabilities once', function () {
    $parent = LegacyParentScopedFilterCompatibilityResource::create([
        'type' => LegacyParentScopedFilterCompatibilityResource::$type,
        'title' => '[P1] Scoped record',
        'priority' => 'urgent',
    ]);
    LegacyParentScopedFilterCompatibilityResource::create([
        'type' => LegacyParentScopedFilterCompatibilityResource::$type,
        'title' => '[P1] Same tenant outside parent',
        'priority' => 'urgent',
    ]);

    if (config('aura.teams')) {
        LegacyParentScopedFilterCompatibilityResource::withoutGlobalScopes()->create([
            'type' => LegacyParentScopedFilterCompatibilityResource::$type,
            'title' => '[P1] Other tenant outside parent',
            'team_id' => $this->user->current_team_id + 1000,
            'priority' => 'urgent',
        ]);
    }

    $test = livewire(LegacyScopedApplyFilterBasedOnTypeTable::class, [
        'model' => $parent,
        'parent' => $parent,
    ]);

    LegacyPackagePriorityFilter::$applyCount = 0;

    $test->set('filters.custom', [[
        'filters' => [[
            'name' => 'priority',
            'operator' => 'is',
            'value' => 'urgent',
        ]],
    ]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$parent->id]);

    expect($test->instance()->legacyHookCalls)->toBeGreaterThan(0)
        ->and(LegacyPackagePriorityFilter::$applyCount)->toBe(1);
});

test('legacy adapters retain fail closed atomic payload validation and bound values', function () {
    $tag = Tag::create(['title' => 'Security tag']);
    $record = LegacyFilterCompatibilityResource::create([
        'type' => LegacyFilterCompatibilityResource::$type,
        'title' => 'Security canary',
        'summary' => 'needle',
        'topics' => [$tag->id],
        'segments' => [1],
    ]);
    $test = livewire(LegacyScopedApplyFilterBasedOnTypeTable::class, ['model' => $record]);

    foreach ([
        [['filters' => [['name' => 'unknown', 'operator' => 'contains', 'value' => 'needle']]]],
        [['filters' => [['name' => 'summary', 'operator' => 'contains', 'value' => [['needle']]]]]],
        [['filters' => [['name' => 'topics', 'operator' => 'contains', 'value' => [$tag->id, null]]]]],
        [['filters' => [['name' => 'segments', 'operator' => 'contains', 'value' => [1, null]]]]],
    ] as $payload) {
        $test
            ->set('filters.custom', $payload)
            ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
    }

    $hostileValue = "needle%' or 1=1 --";
    $test->set('filters.custom', [[
        'filters' => [[
            'name' => 'summary',
            'operator' => 'contains',
            'value' => $hostileValue,
        ]],
    ]]);

    $query = $test->instance()->rowsQuery();

    expect($query->getBindings())->toContain('%'.$hostileValue.'%')
        ->and($query->pluck($query->getModel()->getQualifiedKeyName()))->toBeEmpty();
});

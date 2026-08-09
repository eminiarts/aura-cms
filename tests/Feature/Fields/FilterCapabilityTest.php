<?php

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\AdvancedSelect;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Tags;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Tag;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;

use function Pest\Livewire\livewire;

class FilterCapabilityResource extends Resource
{
    public static ?string $slug = 'filter-capability-resource';

    public static string $type = 'FilterCapabilityResource';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Stage',
                'slug' => 'stage',
                'type' => Select::class,
                'options' => [
                    'draft' => 'Draft',
                    7 => 'Seven',
                    '' => 'Blank value',
                    'missing-label' => null,
                ],
            ],
            [
                'name' => 'Status',
                'slug' => 'status_choice',
                'type' => Status::class,
                'on_index' => false,
                'options' => [
                    ['key' => 'open', 'value' => 'Open', 'color' => 'green'],
                    ['key' => 9, 'value' => 'Nine', 'color' => 'blue'],
                    ['key' => '', 'value' => 'Blank value'],
                    ['key' => 'missing-label', 'value' => null],
                    ['unexpected' => 'shape'],
                    null,
                ],
            ],
            [
                'name' => 'Enabled',
                'slug' => 'enabled',
                'type' => Boolean::class,
            ],
            [
                'name' => 'Published On',
                'slug' => 'published_on',
                'type' => Date::class,
            ],
            [
                'name' => 'Topics',
                'slug' => 'topics',
                'type' => Tags::class,
                'resource' => Tag::class,
                'create' => false,
                'on_index' => false,
            ],
            [
                'name' => 'Priority',
                'slug' => 'priority',
                'type' => PackagePriorityField::class,
                'on_index' => false,
            ],
            [
                'name' => 'Reviewed On',
                'slug' => 'reviewed_on',
                'type' => PackageDateRangeField::class,
                'on_index' => false,
            ],
        ];
    }
}

class PackageDateRangeField extends Date
{
    public function filterCapability(Resource $model, array $field): FilterCapability
    {
        return FilterCapability::dateRange([
            'date_between' => 'is between',
        ]);
    }
}

class PackagePriorityField extends Field
{
    public function filterCapability(Resource $model, array $field): FilterCapability
    {
        return FilterCapability::custom(
            component: 'test-filters::priority',
            operators: ['is' => 'is'],
            queryHandler: PackagePriorityFilter::class,
            values: [
                'urgent' => 'Urgent',
                'routine' => 'Routine',
            ],
        );
    }
}

class PackagePriorityFilter implements AppliesFieldFilter
{
    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        array $filter,
        FilterCapability $capability,
    ): void {
        $prefix = $filter['value'] === 'urgent' ? '[P1]%' : '[Routine]%';

        $query->where($query->getModel()->qualifyColumn('title'), 'like', $prefix);
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new FilterCapabilityResource);
});

test('choice fields publish canonical option filter capabilities', function () {
    $resource = new FilterCapabilityResource;
    $selectField = $resource->fieldBySlug('stage');
    $statusField = $resource->fieldBySlug('status_choice');

    $select = new Select;
    $status = new Status;

    expect($select->getFilterValues($resource, $selectField))->toBe($selectField['options'])
        ->and($status->getFilterValues($resource, $statusField))->toBe($statusField['options'])
        ->and($select->filterCapability($resource, $selectField))->toBeInstanceOf(FilterCapability::class)
        ->and($select->filterCapability($resource, $selectField)->toArray())->toMatchArray([
            'type' => FilterCapability::OPTION,
            'component' => 'aura::fields.filters.option',
            'values' => [
                ['value' => 'draft', 'wire_value' => 'draft', 'label' => 'Draft'],
                ['value' => 7, 'wire_value' => '7', 'label' => 'Seven'],
            ],
        ])
        ->and($status->filterCapability($resource, $statusField)->toArray())->toMatchArray([
            'type' => FilterCapability::OPTION,
            'component' => 'aura::fields.filters.option',
            'values' => [
                ['value' => 'open', 'wire_value' => 'open', 'label' => 'Open'],
                ['value' => 9, 'wire_value' => '9', 'label' => 'Nine'],
            ],
        ]);
});

test('advanced selects preserve their declared UI for relation and stored-value modes', function () {
    $resource = new FilterCapabilityResource;
    $advancedSelect = new AdvancedSelect;
    $relationField = [
        'name' => 'Owner',
        'slug' => 'owner',
        'type' => AdvancedSelect::class,
        'resource' => User::class,
    ];
    $storedField = $relationField + ['polymorphic_relation' => false];

    expect($advancedSelect->filterCapability($resource, $relationField)->toArray())
        ->toMatchArray([
            'type' => FilterCapability::RELATIONSHIP,
            'component' => 'aura::fields.filters.advanced-select',
        ])
        ->and($advancedSelect->filterCapability($resource, $storedField)->toArray())
        ->toMatchArray([
            'type' => FilterCapability::CUSTOM,
            'component' => 'aura::fields.filters.advanced-select',
        ]);
});

test('table filter UI consumes field capabilities without class name dispatch', function () {
    $resource = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Capability test',
        'stage' => 'draft',
        'status_choice' => 'open',
    ]);

    $component = livewire(Table::class, ['model' => $resource]);
    $fieldsForFilter = $component->instance()->fieldsForFilter();

    expect($fieldsForFilter['stage'])
        ->not->toHaveKey('type')
        ->toHaveKey('filter.type', FilterCapability::OPTION)
        ->toHaveKey('filter.component', 'aura::fields.filters.option');

    $component
        ->call('addFilterGroup')
        ->assertSet('filters.custom.0.filters.0.operator', 'is')
        ->assertSeeHtml('value="draft"')
        ->assertSee('Draft');
});

test('option capabilities validate and preserve values before applying generated queries', function () {
    $draft = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Draft record',
        'stage' => 'draft',
        'status_choice' => 'open',
    ]);
    $numeric = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Numeric record',
        'stage' => 7,
        'status_choice' => 9,
    ]);
    FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Invalid stored record',
        'stage' => 'forged',
        'status_choice' => 'forged',
    ]);

    $component = livewire(Table::class, ['model' => $draft])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'stage',
                'operator' => 'is',
                'value' => 'draft',
            ]],
        ]]);

    $query = $component->instance()->rowsQuery();

    expect($query->pluck($query->getModel()->getQualifiedKeyName())->all())->toBe([$draft->id])
        ->and($query->toSql())->toContain('exists')
        ->and($query->getBindings())->toContain('draft');

    $component
        ->set('filters.custom.0.filters.0.value', '7')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$numeric->id]);

    expect($component->instance()->rowsQuery()->getBindings())->toContain(7);

    $component
        ->set('filters.custom.0.filters.0.value', 'forged')
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom.0.filters.0.operator', 'forged_operator')
        ->set('filters.custom.0.filters.0.value', 'draft')
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom.0.filters.0.operator', ['forged_operator'])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom.0.filters.0.operator', 'is')
        ->set('filters.custom.0.filters.0.value', ['draft'])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom.0.filters.0.name', 'status_choice')
        ->set('filters.custom.0.filters.0.operator', 'is')
        ->set('filters.custom.0.filters.0.value', 'open')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$draft->id]);
});

test('boolean and date capabilities render typed controls and apply zero and date values', function () {
    $disabled = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Disabled record',
        'enabled' => false,
        'published_on' => '2026-01-10',
    ]);
    $enabled = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Enabled record',
        'enabled' => true,
        'published_on' => '2026-03-20',
    ]);

    $component = livewire(Table::class, ['model' => $disabled]);
    $fields = $component->instance()->fieldsForFilter();

    expect($fields['enabled'])
        ->toHaveKey('filter.type', FilterCapability::BOOLEAN)
        ->toHaveKey('filter.component', 'aura::fields.filters.boolean')
        ->and($fields['published_on'])
        ->toHaveKey('filter.type', FilterCapability::DATE)
        ->toHaveKey('filter.component', 'aura::fields.filters.date');

    $component
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'enabled',
                'operator' => 'is',
                'value' => '0',
            ]],
        ]])
        ->assertSeeHtml('value="0"')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$disabled->id])
        ->set('filters.custom.0.filters.0.name', 'published_on')
        ->set('filters.custom.0.filters.0.operator', 'date_before')
        ->set('filters.custom.0.filters.0.value', '2026-02-01')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$disabled->id])
        ->set('filters.custom.0.filters.0.value', 'not-a-date')
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom.0.filters.0.value', '')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === [$disabled->id, $enabled->id]);
});

test('relationship capabilities drive tags UI and pivot queries', function () {
    $firstTag = Tag::create(['title' => 'First tag']);
    $secondTag = Tag::create(['title' => 'Second tag']);

    $first = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'First tagged record',
        'topics' => [$firstTag->id],
    ]);
    $second = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Second tagged record',
        'topics' => [$secondTag->id],
    ]);

    $component = livewire(Table::class, ['model' => $first]);
    $fields = $component->instance()->fieldsForFilter();

    expect($fields['topics'])
        ->toHaveKey('filter.type', FilterCapability::RELATIONSHIP)
        ->toHaveKey('filter.component', 'aura::fields.filters.tags');

    $component->set('filters.custom', [[
        'filters' => [[
            'name' => 'topics',
            'operator' => 'contains',
            'value' => [$secondTag->id],
        ]],
    ]]);

    $query = $component->instance()->rowsQuery();

    expect($query->toSql())->toContain('post_relations')
        ->and($query->pluck($query->getModel()->getQualifiedKeyName())->all())->toBe([$second->id]);

    $component
        ->set('filters.custom.0.filters.0.operator', 'does_not_contain')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$first->id])
        ->set('filters.custom.0.filters.0.operator', 'contains')
        ->set('filters.custom.0.filters.0.value', null)
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === [$first->id, $second->id]);
});

test('package fields declare custom UI query behavior and date ranges at the capability seam', function () {
    $urgent = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => '[P1] Contact customer',
        'priority' => 'urgent',
        'reviewed_on' => '2026-02-10',
    ]);
    $routine = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => '[Routine] File notes',
        'priority' => 'routine',
        'reviewed_on' => '2026-04-10',
    ]);

    $component = livewire(Table::class, ['model' => $urgent]);
    $fields = $component->instance()->fieldsForFilter();

    expect($fields['priority'])
        ->toHaveKey('filter.type', FilterCapability::CUSTOM)
        ->toHaveKey('filter.component', 'test-filters::priority')
        ->toHaveKey('filter.query', PackagePriorityFilter::class)
        ->and($fields['reviewed_on'])
        ->toHaveKey('filter.type', FilterCapability::DATE_RANGE)
        ->toHaveKey('filter.component', 'aura::fields.filters.date-range');

    $component
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'priority',
                'operator' => 'is',
                'value' => 'urgent',
            ]],
        ]])
        ->assertSeeHtml('data-package-filter="priority"')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$urgent->id])
        ->set('filters.custom.0.filters.0.name', 'reviewed_on')
        ->set('filters.custom.0.filters.0.operator', 'date_between')
        ->set('filters.custom.0.filters.0.value', [
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ])
        ->assertSeeHtml('type="date"')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$urgent->id])
        ->set('filters.custom.0.filters.0.value.to', 'invalid')
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());

    expect($routine->id)->not->toBe($urgent->id);
});

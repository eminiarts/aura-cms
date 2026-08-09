<?php

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\AdvancedSelect;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Checkbox;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\Datetime;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Fields\Filters\FilterOptionNormalizer;
use Aura\Base\Fields\Radio;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Tags;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Tag;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
                'format' => 'd.m.Y',
            ],
            [
                'name' => 'Occurred At',
                'slug' => 'occurred_at',
                'type' => Datetime::class,
                'format' => 'd.m.Y H:i',
                'on_index' => false,
            ],
            [
                'name' => 'Contact Method',
                'slug' => 'contact_method',
                'type' => Radio::class,
                'options' => [
                    ['key' => 'email', 'value' => 'Email label'],
                    ['key' => 'phone', 'value' => 'Phone label'],
                ],
                'on_index' => false,
            ],
            [
                'name' => 'Segments',
                'slug' => 'segments',
                'type' => Checkbox::class,
                'options' => [
                    ['key' => 1, 'value' => 'One'],
                    ['key' => 10, 'value' => 'Ten'],
                ],
                'on_index' => false,
            ],
            [
                'name' => 'Typed Segments',
                'slug' => 'typed_segments',
                'type' => Checkbox::class,
                'options' => [
                    ['key' => false, 'value' => 'False'],
                    ['key' => 0, 'value' => 'Integer zero'],
                    ['key' => '0', 'value' => 'String zero'],
                ],
                'on_index' => false,
            ],
            [
                'name' => 'Stored People',
                'slug' => 'stored_people',
                'type' => AdvancedSelect::class,
                'resource' => User::class,
                'polymorphic_relation' => false,
                'multiple' => true,
                'on_index' => false,
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

class CollidingRadioFilterResource extends Resource
{
    public static ?string $slug = 'colliding-radio-filter-resource';

    public static string $type = 'CollidingRadioFilterResource';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Typed choice',
                'slug' => 'typed_choice',
                'type' => Radio::class,
                'options' => [
                    ['key' => false, 'value' => 'False'],
                    ['key' => 0, 'value' => 'Integer zero'],
                    ['key' => '0', 'value' => 'String zero'],
                ],
            ],
        ];
    }
}

class CollidingCustomTableRadioFilterResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'colliding-custom-table-radio-filter-resource';

    public $timestamps = false;

    public static string $type = 'CollidingCustomTableRadioFilterResource';

    public static bool $usesMeta = false;

    protected $fillable = ['typed_choice'];

    protected $table = 'colliding_custom_table_choices';

    public static function getFields(): array
    {
        return CollidingRadioFilterResource::getFields();
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

test('dynamic Livewire field and operator updates reset stale filter values', function () {
    $resource = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Hook test',
        'stage' => 'draft',
        'status_choice' => 'open',
    ]);

    livewire(Table::class, ['model' => $resource])
        ->call('addFilterGroup')
        ->set('filters.custom.0.filters.0.value', 'draft')
        ->set('filters.custom.0.filters.0.operator', 'is_not')
        ->assertSet('filters.custom.0.filters.0.value', null)
        ->set('filters.custom.0.filters.0.value', 'draft')
        ->set('filters.custom.0.filters.0.name', 'status_choice')
        ->assertSet('filters.custom.0.filters.0.operator', 'contains')
        ->assertSet('filters.custom.0.filters.0.value', null);
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
        'published_on' => '10.01.2026',
    ]);
    $enabled = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Enabled record',
        'enabled' => true,
        'published_on' => '20.03.2026',
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

test('datetime capabilities implement chronological before and after semantics', function () {
    $before = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Before midnight',
        'occurred_at' => '31.12.2025 23:59',
    ]);
    $after = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'After midnight',
        'occurred_at' => '01.01.2026 00:01',
    ]);

    $component = livewire(Table::class, ['model' => $before]);

    expect($component->instance()->fieldsForFilter()['occurred_at'])
        ->toHaveKey('filter.type', FilterCapability::DATETIME)
        ->toHaveKey('filter.component', 'aura::fields.filters.datetime');

    $component
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'occurred_at',
                'operator' => 'before',
                'value' => '2026-01-01T00:00',
            ]],
        ]])
        ->assertSeeHtml('type="datetime-local"')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$before->id])
        ->set('filters.custom.0.filters.0.operator', 'after')
        ->set('filters.custom.0.filters.0.value', '2026-01-01T00:00')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$after->id]);
});

test('radio and checkbox capabilities filter by stable option values', function () {
    $one = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'One segment',
        'contact_method' => 'email',
        'segments' => [1],
    ]);
    $ten = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Ten segment',
        'contact_method' => 'phone',
        'segments' => [10],
    ]);

    $component = livewire(Table::class, ['model' => $one]);
    $fields = $component->instance()->fieldsForFilter();

    expect($fields['contact_method'])
        ->toHaveKey('filter.type', FilterCapability::OPTION)
        ->toHaveKey('filter.values.0.value', 'email')
        ->toHaveKey('filter.values.0.label', 'Email label')
        ->and($fields['segments'])
        ->toHaveKey('filter.type', FilterCapability::OPTION)
        ->toHaveKey('filter.values.0.value', 1)
        ->toHaveKey('filter.values.1.value', 10);

    $emailWireValue = $fields['contact_method']['filter']['values'][0]['wire_value'];
    $oneWireValue = $fields['segments']['filter']['values'][0]['wire_value'];

    $component
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'contact_method',
                'operator' => 'is',
                'value' => $emailWireValue,
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$one->id])
        ->set('filters.custom.0.filters.0.name', 'segments')
        ->set('filters.custom.0.filters.0.operator', 'contains')
        ->set('filters.custom.0.filters.0.value', $oneWireValue)
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$one->id]);

    expect($ten->id)->not->toBe($one->id);
});

test('saved status contains and boolean equals operators remain compatible', function () {
    $matching = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Matching saved filter',
        'status_choice' => 'open',
        'enabled' => false,
    ]);
    FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Other saved filter',
        'status_choice' => 9,
        'enabled' => true,
    ]);

    $component = livewire(Table::class, ['model' => $matching]);

    $component
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'status_choice',
                'operator' => 'contains',
                'value' => 'open',
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id])
        ->set('filters.custom.0.filters.0.name', 'enabled')
        ->set('filters.custom.0.filters.0.operator', 'equals')
        ->set('filters.custom.0.filters.0.value', '0')
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id]);
});

test('option normalization safely preserves strict scalar identities', function () {
    $normalizer = new FilterOptionNormalizer;
    $mixed = [
        ['value' => false, 'label' => 'False'],
        ['value' => 0, 'label' => 'Integer zero'],
        ['value' => '0', 'label' => 'String zero'],
        null,
        new stdClass,
        ['unexpected' => 'shape'],
    ];
    $reservedWireValue = [
        ...$mixed,
        ['value' => '__aura_filter:Ym9vbDow', 'label' => 'Reserved-looking string'],
    ];

    expect($normalizer->normalize(null))->toBe([])
        ->and($normalizer->normalize('invalid'))->toBe([])
        ->and($normalizer->normalize(123))->toBe([])
        ->and($normalizer->normalize($mixed))->toHaveCount(3)
        ->and(array_column($normalizer->normalize($mixed), 'value'))->toBe([false, 0, '0'])
        ->and(array_column($normalizer->normalize($mixed), 'wire_value'))->each->toBeString()
        ->and(array_unique(array_column($normalizer->normalize($mixed), 'wire_value')))->toHaveCount(3)
        ->and($normalizer->normalize($mixed))->toBe($normalizer->normalize($mixed))
        ->and($normalizer->normalize($reservedWireValue))->toHaveCount(4)
        ->and(array_unique(array_column($normalizer->normalize($reservedWireValue), 'wire_value')))->toHaveCount(4)
        ->and($normalizer->normalize($reservedWireValue)[3]['wire_value'])->not->toBe('__aura_filter:Ym9vbDow');
});

test('scalar choice fields reject option identities that collapse in form and database storage', function () {
    $resource = new CollidingRadioFilterResource;
    $field = $resource->fieldBySlug('typed_choice');

    foreach ([new Radio, new Select, new Status] as $fieldInstance) {
        expect(fn () => $fieldInstance->filterCapability($resource, $field))
            ->toThrow(InvalidArgumentException::class, 'cannot distinguish scalar option values')
            ->and(fn () => $fieldInstance->set($resource, $field, false))
            ->toThrow(InvalidArgumentException::class, 'cannot distinguish scalar option values');
    }

    foreach ([false, 0, '0'] as $value) {
        expect(fn () => CollidingRadioFilterResource::create([
            'type' => CollidingRadioFilterResource::$type,
            'title' => 'Rejected scalar choice',
            'typed_choice' => $value,
        ]))->toThrow(InvalidArgumentException::class, 'cannot distinguish scalar option values');
    }

    expect(CollidingRadioFilterResource::query()->count())->toBe(0);

    Schema::create('colliding_custom_table_choices', function (Blueprint $table): void {
        $table->id();
        $table->string('typed_choice')->nullable();
        $table->foreignId('team_id')->nullable();
    });

    try {
        expect(fn () => CollidingCustomTableRadioFilterResource::create([
            'typed_choice' => false,
        ]))->toThrow(InvalidArgumentException::class, 'cannot distinguish scalar option values')
            ->and(CollidingCustomTableRadioFilterResource::query()->count())->toBe(0);
    } finally {
        Schema::dropIfExists('colliding_custom_table_choices');
    }
});

test('stored AdvancedSelect filtering uses exact portable JSON membership', function () {
    $one = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Stored one',
        'stored_people' => [1],
    ]);
    FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Stored ten',
        'stored_people' => [10],
    ]);

    $component = livewire(Table::class, ['model' => $one])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'stored_people',
                'operator' => 'contains',
                'value' => [1],
            ]],
        ]]);
    $query = $component->instance()->rowsQuery();
    $connection = $query->getConnection();
    $originalGrammar = $connection->getQueryGrammar();

    try {
        $connection->setQueryGrammar(new MySqlGrammar($connection));
        $mysqlSql = $component->instance()->rowsQuery()->toSql();
    } finally {
        $connection->setQueryGrammar($originalGrammar);
    }

    expect($query->pluck($query->getModel()->getQualifiedKeyName())->all())->toBe([$one->id])
        ->and($query->toSql())->toContain('json_each')
        ->and($query->toSql())->not->toContain(' like ')
        ->and($mysqlSql)->toContain('json_contains');
});

test('sqlite JSON membership preserves boolean integer and string identities', function () {
    $boolean = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Boolean false segment',
        'typed_segments' => [false],
    ]);
    $integer = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Integer zero segment',
        'typed_segments' => [0],
    ]);
    $string = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'String zero segment',
        'typed_segments' => ['0'],
    ]);

    $component = livewire(Table::class, ['model' => $boolean]);
    $values = $component->instance()->fieldsForFilter()['typed_segments']['filter']['values'];

    expect(array_column($values, 'value'))->toBe([false, 0, '0']);

    foreach ([$boolean, $integer, $string] as $index => $expected) {
        $component
            ->set('filters.custom', [[
                'filters' => [[
                    'name' => 'typed_segments',
                    'operator' => 'contains',
                    'value' => $values[$index]['wire_value'],
                ]],
            ]])
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$expected->id]);
    }

    $component
        ->set('filters.custom.0.filters.0.operator', 'does_not_contain')
        ->set('filters.custom.0.filters.0.value', $values[0]['wire_value'])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === [$integer->id, $string->id]);
});

test('legacy flat saved filter lists remain queryable', function () {
    $matching = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Legacy saved filter',
        'stage' => 'draft',
    ]);
    FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Legacy non-match',
        'stage' => 7,
    ]);

    livewire(Table::class, ['model' => $matching])
        ->set('filters.custom', [[
            'name' => 'stage',
            'operator' => 'is',
            'value' => 'draft',
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id]);
});

test('filter groups are evaluated left associatively', function () {
    FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'A only',
        'stage' => 'draft',
        'status_choice' => 9,
        'enabled' => false,
    ]);
    $aAndC = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'A and C',
        'stage' => 'draft',
        'status_choice' => 9,
        'enabled' => true,
    ]);
    $bAndC = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'B and C',
        'stage' => 7,
        'status_choice' => 'open',
        'enabled' => true,
    ]);
    FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'B only',
        'stage' => 7,
        'status_choice' => 'open',
        'enabled' => false,
    ]);

    livewire(Table::class, ['model' => $aAndC])
        ->set('filters.custom', [
            [
                'filters' => [[
                    'name' => 'stage',
                    'operator' => 'is',
                    'value' => 'draft',
                ]],
            ],
            [
                'operator' => 'or',
                'filters' => [[
                    'name' => 'status_choice',
                    'operator' => 'is',
                    'value' => 'open',
                ]],
            ],
            [
                'operator' => 'and',
                'filters' => [[
                    'name' => 'enabled',
                    'operator' => 'is',
                    'value' => '1',
                ]],
            ],
        ])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === [$aAndC->id, $bAndC->id]);
});

test('invalid handlers operators and hostile group payloads fail closed', function () {
    expect(fn () => FilterCapability::custom(
        component: 'aura::fields.filters.text',
        operators: ['is' => 'is'],
        queryHandler: stdClass::class,
    ))->toThrow(InvalidArgumentException::class);

    $matching = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Must not be broadened',
        'stage' => 'draft',
    ]);
    $component = livewire(Table::class, ['model' => $matching]);

    $component
        ->set('filters.custom', [
            [
                'filters' => [[
                    'name' => 'stage',
                    'operator' => 'is',
                    'value' => 7,
                ]],
            ],
            [
                'operator' => 'or/**/1=1',
                'filters' => [[
                    'name' => 'stage',
                    'operator' => 'is',
                    'value' => 'draft',
                ]],
            ],
        ])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'stage',
                'operator' => 'is',
                'value' => 'draft',
                'main_operator' => ['or'],
            ], [
                'name' => 'stage',
                'operator' => 'is',
                'value' => 'draft',
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom', 'hostile')
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'stage',
                'operator' => null,
                'value' => 'draft',
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom', [[
            'filters' => [[
                'filters' => [[
                    'name' => 'stage',
                    'operator' => 'is',
                    'value' => 'draft',
                ]],
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom', [[
            'filters' => [[
                'name' => null,
                'operator' => null,
                'value' => null,
                'options' => [],
                'unknown' => ['name' => 'stage'],
            ]],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty())
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'stage',
                'operator' => 'is',
                'value' => 'draft',
            ]],
            'unknown' => ['operator' => 'or'],
        ]])
        ->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
});

test('recognized empty filter placeholders remain inert in any group position', function () {
    $matching = FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Expected match',
        'stage' => 'draft',
    ]);
    FilterCapabilityResource::create([
        'type' => FilterCapabilityResource::$type,
        'title' => 'Expected non-match',
        'stage' => 7,
    ]);

    livewire(Table::class, ['model' => $matching])
        ->set('filters.custom', [
            ['filters' => []],
            [
                'filters' => [[
                    'name' => null,
                    'operator' => null,
                    'value' => null,
                    'options' => [],
                ]],
            ],
            [
                'operator' => 'and',
                'filters' => [[
                    'name' => 'stage',
                    'operator' => 'is',
                    'value' => 'draft',
                ]],
            ],
            ['operator' => 'or', 'filters' => []],
        ])
        ->assertViewHas('rows', fn ($rows) => $rows->pluck('id')->all() === [$matching->id]);
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
        ->set('filters.custom.0.filters.0.value', [$secondTag->id])
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

<?php

namespace Tests\Feature\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueContract;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Exceptions\InvalidFieldValue;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\Datetime;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Number;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View as ResourceView;
use Aura\Base\Resource;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class Core10LegacyValueField extends Field
{
    public function display($field, $value, $model)
    {
        return 'displayed:'.$value;
    }

    public function get($class, $value, $field = null)
    {
        return 'hydrated:'.$value;
    }

    public function set($post, $field, $value)
    {
        return 'stored:'.$value;
    }
}

class Core10ContextValueField extends Field
{
    public function displayValue(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed {
        return $context->value.':'.$value;
    }
}

class Core10BooleanCast implements CastsAttributes
{
    public static array $setValues = [];

    public function get(Model $model, string $key, mixed $value, array $attributes): bool
    {
        return $value === 'yes';
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        self::$setValues[] = $value;

        return $value ? 'yes' : 'no';
    }
}

class Core10NullCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}

class Core10EloquentPipelineResource extends Resource
{
    public static $customTable = true;

    public static array $mutatorValues = [];

    public static ?string $slug = 'core-10-eloquent-pipeline';

    public static string $type = 'Core10EloquentPipeline';

    public static bool $usesMeta = false;

    protected $fillable = [
        'cast_boolean',
        'mutated_boolean',
        'null_cast_value',
        'null_accessor_value',
    ];

    protected $table = 'core_10_eloquent_pipeline_values';

    public static function getFields(): array
    {
        return collect([
            'cast_boolean',
            'mutated_boolean',
            'null_cast_value',
            'null_accessor_value',
        ])->map(fn (string $slug): array => [
            'name' => $slug,
            'slug' => $slug,
            'type' => Boolean::class,
        ])->all();
    }

    public function setMutatedBooleanAttribute(mixed $value): void
    {
        self::$mutatorValues[] = $value;
        $this->attributes['mutated_boolean'] = $value ? 'yes' : 'no';
    }

    protected function casts(): array
    {
        return [
            'cast_boolean' => Core10BooleanCast::class,
            'null_cast_value' => Core10NullCast::class,
        ];
    }

    protected function nullAccessorValue(): Attribute
    {
        return Attribute::make(get: fn (mixed $value): mixed => null);
    }
}

class Core10DstResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-dst';

    public static string $type = 'Core10Dst';

    public static bool $usesMeta = false;

    protected $fillable = ['occurred_at'];

    protected $table = 'core_10_dst_values';

    public static function getFields(): array
    {
        return [[
            'name' => 'Occurred at',
            'slug' => 'occurred_at',
            'type' => Datetime::class,
            'format' => 'd.m.Y H:i',
            'display_format' => 'Y-m-d H:i P T',
            'input_timezone' => 'Europe/Zurich',
            'display_timezone' => 'Europe/Zurich',
            'storage_timezone' => 'UTC',
        ]];
    }
}

class Core10ValueResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-value';

    public static string $type = 'Core10Value';

    public static bool $usesMeta = true;

    protected $fillable = [
        'integer_value',
        'decimal_value',
        'date_value',
        'datetime_value',
        'boolean_value',
    ];

    protected $table = 'core_10_values';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Integer value',
                'slug' => 'integer_value',
                'type' => Number::class,
                'number_type' => 'integer',
            ],
            [
                'name' => 'Decimal value',
                'slug' => 'decimal_value',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
            ],
            [
                'name' => 'Date value',
                'slug' => 'date_value',
                'type' => Date::class,
                'format' => 'd.m.Y',
                'display_format' => 'M j, Y',
            ],
            [
                'name' => 'Datetime value',
                'slug' => 'datetime_value',
                'type' => Datetime::class,
                'format' => 'd.m.Y H:i',
                'display_format' => 'M j, Y H:i',
                'input_timezone' => 'Europe/Zurich',
                'display_timezone' => 'America/New_York',
                'storage_timezone' => 'UTC',
            ],
            [
                'name' => 'Boolean value',
                'slug' => 'boolean_value',
                'type' => Boolean::class,
            ],
            [
                'name' => 'Meta decimal',
                'slug' => 'meta_decimal',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 12,
                'scale' => 3,
            ],
            [
                'name' => 'Meta boolean',
                'slug' => 'meta_boolean',
                'type' => Boolean::class,
            ],
            [
                'name' => 'Meta nullable boolean',
                'slug' => 'meta_nullable_boolean',
                'type' => Boolean::class,
            ],
            [
                'name' => 'Meta empty',
                'slug' => 'meta_empty',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
            ],
        ];
    }
}

class Core10PhysicalValueResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-physical-value';

    public static string $type = 'Core10PhysicalValue';

    public static bool $usesMeta = false;

    protected $fillable = [
        'decimal_value',
        'date_value',
        'datetime_value',
        'boolean_value',
    ];

    protected $table = 'core_10_physical_values';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Decimal value',
                'slug' => 'decimal_value',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 12,
                'scale' => 2,
            ],
            [
                'name' => 'Date value',
                'slug' => 'date_value',
                'type' => Date::class,
                'format' => 'd.m.Y',
            ],
            [
                'name' => 'Datetime value',
                'slug' => 'datetime_value',
                'type' => Datetime::class,
                'format' => 'd.m.Y H:i',
                'input_timezone' => 'Europe/Zurich',
                'display_timezone' => 'Europe/Zurich',
                'storage_timezone' => 'UTC',
            ],
            [
                'name' => 'Boolean value',
                'slug' => 'boolean_value',
                'type' => Boolean::class,
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    if (! Schema::hasTable('core_10_values')) {
        Schema::create('core_10_values', function (Blueprint $table) {
            $table->id();
            $table->integer('integer_value')->nullable();
            $table->decimal('decimal_value', 12, 2)->nullable();
            $table->date('date_value')->nullable();
            $table->timestamp('datetime_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('core_10_physical_values')) {
        Schema::create('core_10_physical_values', function (Blueprint $table) {
            $table->id();
            $table->decimal('decimal_value', 12, 2)->nullable();
            $table->date('date_value')->nullable();
            $table->timestamp('datetime_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('core_10_eloquent_pipeline_values')) {
        Schema::create('core_10_eloquent_pipeline_values', function (Blueprint $table) {
            $table->id();
            $table->string('cast_boolean')->nullable();
            $table->string('mutated_boolean')->nullable();
            $table->string('null_cast_value')->nullable();
            $table->string('null_accessor_value')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('core_10_dst_values')) {
        Schema::create('core_10_dst_values', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    Core10BooleanCast::$setValues = [];
    Core10EloquentPipelineResource::$mutatorValues = [];
});

test('the value contract adapts legacy set get and display hooks', function () {
    $field = new Core10LegacyValueField;

    expect($field)->toBeInstanceOf(FieldValueContract::class)
        ->and($field->normalizeForStorage('value', [], null, FieldValueStorage::Meta))->toBe('stored:value')
        ->and($field->hydrateFromStorage('value', [], null, FieldValueStorage::Physical))->toBe('hydrated:value')
        ->and($field->displayValue('value', [], null, FieldValueContext::View))->toBe('displayed:value');
});

test('field presentation receives an explicit context', function () {
    $field = new Core10ContextValueField;

    expect($field->displayValue('value', [], null, FieldValueContext::Create))->toBe('create:value')
        ->and($field->displayValue('value', [], null, FieldValueContext::Edit))->toBe('edit:value')
        ->and($field->displayValue('value', [], null, FieldValueContext::Index))->toBe('index:value')
        ->and($field->displayValue('value', [], null, FieldValueContext::Export))->toBe('export:value')
        ->and($field->displayValue('value', [], null, FieldValueContext::View))->toBe('view:value');
});

test('resource context display preserves legacy single-argument overrides', function () {
    $resource = new class extends Resource
    {
        public function display($key)
        {
            return 'legacy:'.$key;
        }

        public static function getFields(): array
        {
            return [];
        }

        public function getMeta($key = null)
        {
            return 'legacy-meta:'.$key;
        }

        public function resolveFieldValue(string $slug, $meta = null)
        {
            return 'legacy-value:'.$slug;
        }
    };

    expect($resource->displayInContext('value', FieldValueContext::View))->toBe('legacy:value')
        ->and($resource->getMetaInContext('value', FieldValueContext::Edit))->toBe('legacy-meta:value')
        ->and($resource->resolveFieldValueInContext('value', FieldValueContext::Edit))->toBe('legacy-value:value');
});

test('physical and meta values use the same field normalization and hydration contract', function () {
    $resource = Core10ValueResource::create([
        'integer_value' => '-12',
        'decimal_value' => '1234.567',
        'date_value' => '09.08.2026',
        'datetime_value' => '09.08.2026 18:15',
        'boolean_value' => false,
        'meta_decimal' => '-4.2568',
        'meta_boolean' => false,
        'meta_nullable_boolean' => null,
        'meta_empty' => '',
    ])->refresh();

    expect($resource->integer_value)->toBe(-12)
        ->and((string) $resource->decimal_value)->toBe('1234.57')
        ->and($resource->date_value)->toBe('2026-08-09')
        ->and($resource->datetime_value)->toBe('2026-08-09 16:15:00')
        ->and($resource->boolean_value)->toBe(0)
        ->and($resource->resolveFieldValue('datetime_value'))->toBe('2026-08-09 12:15:00')
        ->and($resource->resolveFieldValue('boolean_value'))->toBeFalse()
        ->and($resource->meta_decimal)->toBe('-4.257')
        ->and($resource->meta_boolean)->toBeFalse()
        ->and($resource->meta_nullable_boolean)->toBeNull()
        ->and($resource->meta_empty)->toBe('');

    expect(DB::table('core_10_values')->where('id', $resource->id)->value('datetime_value'))->toBe('2026-08-09 16:15:00');

    expect(DB::table('meta')->where('metable_id', $resource->id)->where('key', 'meta_boolean')->value('value'))->toBe('0')
        ->and(DB::table('meta')->where('metable_id', $resource->id)->where('key', 'meta_nullable_boolean')->value('value'))->toBeNull()
        ->and(DB::table('meta')->where('metable_id', $resource->id)->where('key', 'meta_empty')->value('value'))->toBe('');
});

test('physical-only custom tables normalize values before persistence', function () {
    $resource = Core10PhysicalValueResource::create([
        'decimal_value' => '-123.456',
        'date_value' => '09.08.2026',
        'datetime_value' => '09.08.2026 18:15',
        'boolean_value' => '0',
    ])->refresh();

    expect((string) $resource->decimal_value)->toBe('-123.46')
        ->and($resource->date_value)->toBe('2026-08-09')
        ->and($resource->datetime_value)->toBe('2026-08-09 16:15:00')
        ->and($resource->resolveFieldValue('datetime_value'))->toBe('2026-08-09 18:15:00')
        ->and($resource->boolean_value)->toBe(0)
        ->and($resource->resolveFieldValue('boolean_value'))->toBeFalse()
        ->and(DB::table('core_10_physical_values')->where('id', $resource->id)->value('datetime_value'))
        ->toBe('2026-08-09 16:15:00');
});

test('physical writes compose Aura normalization before Eloquent casts and mutators', function () {
    $resource = Core10EloquentPipelineResource::create([
        'cast_boolean' => 'false',
        'mutated_boolean' => 'false',
    ]);

    expect(Core10BooleanCast::$setValues)->toBe([false])
        ->and(Core10EloquentPipelineResource::$mutatorValues)->toBe([false])
        ->and(DB::table('core_10_eloquent_pipeline_values')->where('id', $resource->id)->value('cast_boolean'))->toBe('no')
        ->and(DB::table('core_10_eloquent_pipeline_values')->where('id', $resource->id)->value('mutated_boolean'))->toBe('no');
});

test('null returned by an Eloquent cast or accessor is authoritative during field hydration', function () {
    $id = DB::table('core_10_eloquent_pipeline_values')->insertGetId([
        'null_cast_value' => 'legacy-sentinel',
        'null_accessor_value' => 'legacy-sentinel',
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $resource = Core10EloquentPipelineResource::findOrFail($id);

    expect($resource->getRawOriginal('null_cast_value'))->toBe('legacy-sentinel')
        ->and($resource->getRawOriginal('null_accessor_value'))->toBe('legacy-sentinel')
        ->and($resource->resolveFieldValue('null_cast_value'))->toBeNull()
        ->and($resource->resolveFieldValue('null_accessor_value'))->toBeNull();
});

test('create query parameters retain decimal values until field normalization', function () {
    Aura::fake();
    Aura::setModel(new Core10ValueResource);

    Livewire::withQueryParams(['decimal_value' => '3.14'])
        ->test(Create::class, ['slug' => 'core-10-value'])
        ->assertSet('form.fields.decimal_value', '3.14');
});

test('edit hydration and resource display use their requested contexts once', function () {
    $resource = Core10ValueResource::create([
        'date_value' => '09.08.2026',
        'datetime_value' => '09.08.2026 18:15',
    ])->refresh();

    Aura::fake();
    Aura::setModel(new Core10ValueResource);

    Livewire::test(Edit::class, ['id' => $resource->id, 'slug' => 'core-10-value'])
        ->assertSet('form.fields.date_value', '09.08.2026')
        ->assertSet('form.fields.datetime_value', '09.08.2026 12:15');

    expect(trim(strip_tags($resource->display('date_value'))))->toBe('Aug 9, 2026')
        ->and(trim(strip_tags($resource->display('datetime_value'))))->toBe('Aug 9, 2026 12:15')
        ->and(trim(strip_tags($resource->displayInContext('datetime_value', FieldValueContext::View))))->toBe('Aug 9, 2026 12:15');
});

test('the view surface renders zero instead of the empty-value placeholder', function () {
    $resource = Core10ValueResource::create([
        'integer_value' => 0,
        'decimal_value' => '1.00',
        'date_value' => '09.08.2026',
        'datetime_value' => '09.08.2026 18:15',
        'boolean_value' => false,
        'meta_decimal' => '1.000',
        'meta_boolean' => false,
        'meta_nullable_boolean' => true,
        'meta_empty' => '1.00',
    ])->refresh();

    Aura::fake();
    Aura::setModel(new Core10ValueResource);

    $html = Livewire::test(ResourceView::class, ['id' => $resource->id, 'slug' => 'core-10-value'])->html();
    preg_match('/resource-field-integer-value-wrapper(?<field>.*?)resource-field-decimal-value-wrapper/s', $html, $matches);

    expect($matches['field'] ?? null)->toContain('<div class="truncate">')
        ->toContain('            0')
        ->not->toContain('text-gray-400');
});

test('invalid legacy values remain inspectable instead of becoming fabricated values', function () {
    $resource = Core10ValueResource::create([
        'integer_value' => 1,
        'date_value' => '09.08.2026',
        'datetime_value' => '09.08.2026 18:15',
        'meta_decimal' => '1.000',
    ]);

    DB::table('core_10_values')->where('id', $resource->id)->update([
        'integer_value' => 'not-a-number',
        'date_value' => 'not-a-date',
        'datetime_value' => 'not-a-datetime',
    ]);
    DB::table('meta')
        ->where('metable_id', $resource->id)
        ->where('key', 'meta_decimal')
        ->update(['value' => 'legacy-value']);

    $resource = $resource->fresh();

    expect($resource->integer_value)->toBe('not-a-number')
        ->and($resource->date_value)->toBe('not-a-date')
        ->and($resource->datetime_value)->toBe('not-a-datetime')
        ->and($resource->meta_decimal)->toBe('legacy-value');
});

test('date rendering is null safe and date only values never shift timezones', function () {
    config()->set('app.timezone', 'Pacific/Kiritimati');

    $date = new Date;
    $field = ['slug' => 'date_value', 'display_format' => 'Y-m-d'];

    expect($date->displayValue(null, $field, null, FieldValueContext::Index))->not->toContain(now()->format('Y-m-d'))
        ->and(trim(strip_tags($date->displayValue('', $field, null, FieldValueContext::View))))->toBe('')
        ->and(trim(strip_tags($date->displayValue('2026-01-01', $field, null, FieldValueContext::Index))))->toBe('2026-01-01');
});

test('datetime rendering converts from storage timezone and honors both index and view formats', function () {
    $datetime = new Datetime;
    $field = [
        'slug' => 'datetime_value',
        'display_format' => 'Y-m-d H:i T',
        'display_timezone' => 'America/New_York',
        'storage_timezone' => 'UTC',
    ];

    expect(trim(strip_tags($datetime->display($field, '2026-08-09 16:15:00', null))))->toBe('2026-08-09 12:15 EDT')
        ->and(trim(strip_tags($datetime->displayValue('2026-08-09 12:15:00', $field, null, FieldValueContext::View))))->toBe('2026-08-09 12:15 EDT')
        ->and(trim(strip_tags($datetime->displayValue(null, $field, null, FieldValueContext::Index))))->toBe('');
});

test('datetime defaults preserve legacy application-timezone clock values', function () {
    config()->set('app.timezone', 'Europe/Zurich');
    config()->set('aura.fields.datetime.storage_timezone', null);
    config()->set('aura.fields.datetime.display_timezone', null);

    $datetime = new Datetime;
    $field = ['format' => 'd.m.Y H:i'];
    $stored = $datetime->normalizeForStorage('09.08.2026 18:15', $field, null, FieldValueStorage::Meta);

    expect($stored)->toBe('2026-08-09 18:15:00')
        ->and($datetime->hydrateFromStorage($stored, $field, null, FieldValueStorage::Meta, FieldValueContext::Edit))
        ->toBe('09.08.2026 18:15');
});

test('ambiguous edit values retain the exact original instant across presentation contexts', function (string $stored, string $offset) {
    $id = DB::table('core_10_dst_values')->insertGetId([
        'occurred_at' => $stored,
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Aura::fake();
    Aura::setModel(new Core10DstResource);

    Livewire::test(Edit::class, ['id' => $id, 'slug' => 'core-10-dst'])
        ->assertSet('form.fields.occurred_at', '25.10.2026 02:30')
        ->set('form.fields.occurred_at', '25.10.2026 02:30')
        ->call('save')
        ->assertHasNoErrors();

    $resource = Core10DstResource::findOrFail($id);

    expect(DB::table('core_10_dst_values')->where('id', $id)->value('occurred_at'))->toBe($stored)
        ->and(strip_tags($resource->displayInContext('occurred_at', FieldValueContext::Index)))->toContain($offset)
        ->and(strip_tags($resource->displayInContext('occurred_at', FieldValueContext::View)))->toContain($offset)
        ->and(strip_tags($resource->exportFieldValue('occurred_at')))->toContain($offset);
})->with([
    'summer occurrence' => ['2026-10-25 00:30:00', '+02:00 CEST'],
    'winter occurrence' => ['2026-10-25 01:30:00', '+01:00 CET'],
]);

test('datetime writes reject nonexistent and unresolved ambiguous wall-clock values', function (string $value) {
    expect(fn () => Core10DstResource::create(['occurred_at' => $value]))
        ->toThrow(InvalidFieldValue::class);
})->with([
    'spring gap' => '29.03.2026 02:30',
    'unresolved fall overlap' => '25.10.2026 02:30',
]);

test('create accepts an explicit offset and stores the exact instant', function () {
    Aura::fake();
    Aura::setModel(new Core10DstResource);

    Livewire::test(Create::class, ['slug' => 'core-10-dst'])
        ->set('form.fields.occurred_at', '2026-10-25T02:30:00+01:00')
        ->call('save')
        ->assertHasNoErrors();

    expect(DB::table('core_10_dst_values')->orderByDesc('id')->value('occurred_at'))
        ->toBe('2026-10-25 01:30:00');
});

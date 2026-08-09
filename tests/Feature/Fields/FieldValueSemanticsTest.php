<?php

namespace Tests\Feature\Fields;

use Aura\Base\Contracts\FieldPresentationContract;
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
use Aura\Base\Fields\Permissions;
use Aura\Base\Fields\Tags;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View as ResourceView;
use Aura\Base\Resource;
use Aura\Base\Support\ExactDecimal;
use DateTimeInterface;
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

class Core10DocumentedLegacyDisplayValueField extends Field
{
    public function displayValue($value, $model)
    {
        return 'legacy-display:'.$value.':'.($model?->getKey() ?? 'new');
    }
}

class Core10PrefixNormalizingField extends Field
{
    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed {
        return 'normalized:'.$value;
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

class Core10AmbiguousStorageResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-ambiguous-storage';

    public static string $type = 'Core10AmbiguousStorage';

    public static bool $usesMeta = true;

    protected $fillable = ['physical_occurred_at'];

    protected $table = 'core_10_ambiguous_storage_values';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Physical occurred at',
                'slug' => 'physical_occurred_at',
                'type' => Datetime::class,
                'format' => 'Y-m-d H:i:s',
                'display_format' => 'Y-m-d H:i P T',
                'input_timezone' => 'Europe/Zurich',
                'display_timezone' => 'America/New_York',
                'storage_timezone' => 'Europe/Zurich',
            ],
            [
                'name' => 'Meta occurred at',
                'slug' => 'meta_occurred_at',
                'type' => Datetime::class,
                'format' => 'Y-m-d H:i:s',
                'display_format' => 'Y-m-d H:i P T',
                'input_timezone' => 'Europe/Zurich',
                'display_timezone' => 'America/New_York',
                'storage_timezone' => 'Europe/Zurich',
            ],
        ];
    }
}

class Core10NativeDatetimeCastResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-native-datetime-cast';

    public static string $type = 'Core10NativeDatetimeCast';

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
            'display_format' => 'Y-m-d H:i',
            'input_timezone' => 'Europe/Zurich',
            'display_timezone' => 'America/New_York',
            'storage_timezone' => 'UTC',
        ]];
    }

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}

class Core10NullDatetimeCastModel extends Model
{
    protected function casts(): array
    {
        return ['occurred_at' => Core10NullCast::class];
    }
}

class Core10NullDatetimeAccessorModel extends Model
{
    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    protected function occurredAt(): Attribute
    {
        return Attribute::make(get: fn (mixed $value): mixed => null);
    }
}

class Core10ExactNumberResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-exact-number';

    public static string $type = 'Core10ExactNumber';

    public static bool $usesMeta = false;

    protected $fillable = ['exact_integer', 'exact_decimal'];

    protected $table = 'core_10_exact_number_values';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Exact integer',
                'slug' => 'exact_integer',
                'type' => Number::class,
                'number_type' => 'integer',
                'precision' => 65,
            ],
            [
                'name' => 'Exact decimal',
                'slug' => 'exact_decimal',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 65,
                'scale' => 30,
            ],
        ];
    }
}

class Core10ArrayCastResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-array-cast';

    public static string $type = 'Core10ArrayCast';

    public static bool $usesMeta = false;

    protected $fillable = ['permissions'];

    protected $table = 'core_10_array_cast_values';

    public static function getFields(): array
    {
        return [[
            'name' => 'Permissions',
            'slug' => 'permissions',
            'type' => Permissions::class,
        ]];
    }

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }
}

class Core10PackedProvenanceResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'core-10-packed-provenance';

    public static string $type = 'Core10PackedProvenance';

    public static bool $usesMeta = false;

    protected $fillable = ['normalized_value'];

    protected $table = 'core_10_packed_provenance_values';

    public static function getFields(): array
    {
        return [[
            'name' => 'Normalized value',
            'slug' => 'normalized_value',
            'type' => Core10PrefixNormalizingField::class,
        ]];
    }
}

class Core10NullDefaultsResource extends Resource
{
    public static ?string $slug = 'core-10-null-defaults';

    public static string $type = 'Core10NullDefaults';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Nullable boolean',
                'slug' => 'nullable_boolean',
                'type' => Boolean::class,
                'default' => null,
            ],
            [
                'name' => 'Nullable tags',
                'slug' => 'nullable_tags',
                'type' => Tags::class,
                'default' => null,
            ],
        ];
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
                'name' => 'Meta date',
                'slug' => 'meta_date',
                'type' => Date::class,
                'format' => 'd.m.Y',
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
            [
                'name' => 'Meta exact decimal',
                'slug' => 'meta_exact_decimal',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 65,
                'scale' => 30,
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
            $table->dateTime('occurred_at')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('core_10_ambiguous_storage_values')) {
        Schema::create('core_10_ambiguous_storage_values', function (Blueprint $table) {
            $table->id();
            $table->timestamp('physical_occurred_at')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('core_10_exact_number_values')) {
        $number = new Number;
        $fields = collect(Core10ExactNumberResource::getFields())->keyBy('slug');

        Schema::create('core_10_exact_number_values', function (Blueprint $table) use ($fields, $number) {
            $table->id();
            $number->columnDefinition($fields['exact_integer'])->addTo($table, 'exact_integer');
            $number->columnDefinition($fields['exact_decimal'])->addTo($table, 'exact_decimal');
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('core_10_array_cast_values')) {
        Schema::create('core_10_array_cast_values', function (Blueprint $table) {
            $table->id();
            $table->text('permissions')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('core_10_packed_provenance_values')) {
        Schema::create('core_10_packed_provenance_values', function (Blueprint $table) {
            $table->id();
            $table->string('normalized_value')->nullable();
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
        ->and((string) $field->displayValue('value', [], null, FieldValueContext::View))->toBe('displayed:value');
});

test('documented two argument displayValue subclasses load and use the typed adapter', function () {
    $field = new Core10DocumentedLegacyDisplayValueField;
    $model = new Core10ValueResource;

    expect($field)->toBeInstanceOf(FieldPresentationContract::class)
        ->and((string) $field->presentValue('value', ['slug' => 'legacy'], $model, FieldValueContext::View))
        ->toBe('legacy-display:value:new');
});

test('field presentation receives an explicit context', function () {
    $field = new Core10ContextValueField;

    expect((string) $field->presentValue('value', [], null, FieldValueContext::Create))->toBe('create:value')
        ->and((string) $field->presentValue('value', [], null, FieldValueContext::Edit))->toBe('edit:value')
        ->and((string) $field->presentValue('value', [], null, FieldValueContext::Index))->toBe('index:value')
        ->and((string) $field->presentValue('value', [], null, FieldValueContext::Export))->toBe('export:value')
        ->and((string) $field->presentValue('value', [], null, FieldValueContext::View))->toBe('view:value');
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

test('invalid date creates are rejected before physical or meta persistence', function (string $field, mixed $value) {
    $rowsBefore = DB::table('core_10_values')->count();
    $metaBefore = DB::table('meta')->count();

    expect(fn () => Core10ValueResource::create([$field => $value]))
        ->toThrow(InvalidFieldValue::class)
        ->and(DB::table('core_10_values')->count())->toBe($rowsBefore)
        ->and(DB::table('meta')->count())->toBe($metaBefore);
})->with([
    'physical executable payload' => ['date_value', "', [(window.__auraXss=1)]: '"],
    'meta executable payload' => ['meta_date', "', [(window.__auraXss=1)]: '"],
    'physical impossible date' => ['date_value', '31.02.2026'],
    'meta impossible date' => ['meta_date', '31.02.2026'],
    'physical non-scalar value' => ['date_value', ['2026-08-09']],
    'meta non-scalar value' => ['meta_date', ['2026-08-09']],
]);

test('invalid date edits are rejected without changing physical or meta values', function (string $field, mixed $value) {
    $resource = Core10ValueResource::create([
        'date_value' => '09.08.2026',
        'meta_date' => '09.08.2026',
    ]);

    expect(fn () => $resource->update([$field => $value]))
        ->toThrow(InvalidFieldValue::class)
        ->and(DB::table('core_10_values')->where('id', $resource->id)->value('date_value'))
        ->toBe('2026-08-09')
        ->and(DB::table('meta')
            ->where('metable_id', $resource->id)
            ->where('key', 'meta_date')
            ->value('value'))
        ->toBe('2026-08-09');
})->with([
    'physical executable payload' => ['date_value', "', [(window.__auraXss=1)]: '"],
    'meta executable payload' => ['meta_date', "', [(window.__auraXss=1)]: '"],
    'physical inexact date' => ['date_value', ' 09.08.2026 '],
    'meta inexact date' => ['meta_date', ' 09.08.2026 '],
]);

test('invalid date normalization is rejected before a database driver can handle it', function (string $driver) {
    $originalDriver = config('database.default');

    try {
        config()->set('database.default', $driver);

        expect(fn () => (new Date)->normalizeForStorage(
            "', [(window.__auraXss=1)]: '",
            ['slug' => 'date_value', 'format' => 'Y-m-d'],
            null,
            FieldValueStorage::Physical,
        ))->toThrow(InvalidFieldValue::class);
    } finally {
        config()->set('database.default', $originalDriver);
    }
})->with(['sqlite', 'mysql', 'pgsql']);

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

test('packed physical provenance is consumed before a later literal payload on the same instance', function () {
    $resource = Core10PackedProvenanceResource::create([
        'normalized_value' => 'first',
    ]);

    expect(DB::table('core_10_packed_provenance_values')->where('id', $resource->id)->value('normalized_value'))
        ->toBe('normalized:first');

    $resource->setAttribute('fields', ['normalized_value' => 'second']);
    $resource->save();

    expect(DB::table('core_10_packed_provenance_values')->where('id', $resource->id)->value('normalized_value'))
        ->toBe('normalized:second');
});

test('json field normalization composes with an Eloquent array cast without double encoding', function () {
    $permissions = ['view-post' => true, 'delete-post' => false];
    $resource = Core10ArrayCastResource::create(['permissions' => $permissions])->refresh();

    expect($resource->permissions)->toBe($permissions)
        ->and($resource->resolveFieldValue('permissions'))->toBe($permissions)
        ->and(DB::table('core_10_array_cast_values')->where('id', $resource->id)->value('permissions'))
        ->toBe(json_encode($permissions));
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

test('create treats explicit null defaults as authoritative for boolean and tags fields', function () {
    Aura::fake();
    Aura::setModel(new Core10NullDefaultsResource);

    Livewire::test(Create::class, ['slug' => 'core-10-null-defaults'])
        ->assertSet('form.fields.nullable_boolean', null)
        ->assertSet('form.fields.nullable_tags', null);
});

test('decimal overflow is rejected before a database row is written', function () {
    $before = DB::table('core_10_values')->count();

    expect(fn () => Core10ValueResource::create(['decimal_value' => '12345678901.00']))
        ->toThrow(InvalidFieldValue::class)
        ->and(DB::table('core_10_values')->count())->toBe($before);
});

test('large meta decimals remain exact strings', function () {
    $value = '12345678901234567890123456789012345.123456789012345678901234567890';
    $resource = Core10ValueResource::create(['meta_exact_decimal' => $value])->refresh();

    expect($resource->meta_exact_decimal)->toBe($value)
        ->and(DB::table('meta')
            ->where('metable_id', $resource->id)
            ->where('key', 'meta_exact_decimal')
            ->value('value'))->toBe($value);
});

test('large physical integers and decimals remain exact strings', function () {
    $integer = '12345678901234567890123456789012345678901234567890123456789012345';
    $decimal = '12345678901234567890123456789012345.123456789012345678901234567890';
    $resource = Core10ExactNumberResource::create([
        'exact_integer' => $integer,
        'exact_decimal' => $decimal,
    ])->refresh();

    expect($resource->resolveFieldValue('exact_integer'))->toBe($integer)
        ->and($resource->resolveFieldValue('exact_decimal'))->toBe($decimal)
        ->and(DB::table('core_10_exact_number_values')->where('id', $resource->id)->value('exact_integer'))->toBe($integer)
        ->and(DB::table('core_10_exact_number_values')->where('id', $resource->id)->value('exact_decimal'))->toBe($decimal);
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
        'meta_date' => '09.08.2026',
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
    DB::table('meta')
        ->where('metable_id', $resource->id)
        ->where('key', 'meta_date')
        ->update(['value' => 'legacy-date']);

    $resource = $resource->fresh();

    expect($resource->integer_value)->toBe('not-a-number')
        ->and($resource->date_value)->toBe('not-a-date')
        ->and($resource->datetime_value)->toBe('not-a-datetime')
        ->and($resource->meta_decimal)->toBe('legacy-value')
        ->and($resource->meta_date)->toBe('legacy-date');
});

test('date rendering is null safe and date only values never shift timezones', function () {
    config()->set('app.timezone', 'Pacific/Kiritimati');

    $date = new Date;
    $field = ['slug' => 'date_value', 'display_format' => 'Y-m-d'];

    expect((string) $date->displayValue(null, $field, null, FieldValueContext::Index))->not->toContain(now()->format('Y-m-d'))
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

test('physical datetime hydration uses the declared storage timezone before native Eloquent casts', function () {
    config()->set('app.timezone', 'Europe/Zurich');
    $id = DB::table('core_10_dst_values')->insertGetId([
        'occurred_at' => '2026-08-09 16:15:00',
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $resource = Core10NativeDatetimeCastResource::findOrFail($id);

    expect($resource->occurred_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($resource->resolveFieldValue('occurred_at'))->toBe('2026-08-09 12:15:00')
        ->and(trim(strip_tags($resource->displayInContext('occurred_at', FieldValueContext::View))))
        ->toBe('2026-08-09 12:15');
});

test('custom datetime getters returning null remain authoritative over the raw column', function () {
    $datetime = new Datetime;
    $field = Core10NativeDatetimeCastResource::getFields()[0];

    foreach ([Core10NullDatetimeCastModel::class, Core10NullDatetimeAccessorModel::class] as $modelClass) {
        /** @var Model $model */
        $model = new $modelClass;
        $model->setRawAttributes(['occurred_at' => '2026-08-09 16:15:00'], true);

        expect($model->getRawOriginal('occurred_at'))->toBe('2026-08-09 16:15:00')
            ->and($model->getAttribute('occurred_at'))->toBeNull()
            ->and($datetime->hydrateFromStorage(
                $model->getAttribute('occurred_at'),
                $field,
                $model,
                FieldValueStorage::Physical,
                FieldValueContext::View,
            ))->toBeNull();
    }
});

test('timezone-bearing configured datetime formats are parsed strictly', function (string $format, string $value, string $expected) {
    $datetime = new Datetime;
    $field = [
        'slug' => 'occurred_at',
        'format' => $format,
        'storage_timezone' => 'UTC',
        'input_timezone' => 'Europe/Zurich',
    ];

    expect($datetime->normalizeForStorage($value, $field, null, FieldValueStorage::Meta))->toBe($expected);
})->with([
    'timezone identifier' => ['Y-m-d H:i e', '2026-08-10 14:30 Europe/Zurich', '2026-08-10 12:30:00'],
    'timezone abbreviation' => ['Y-m-d H:i T', '2026-08-10 14:30 CEST', '2026-08-10 12:30:00'],
    'compact offset' => ['Y-m-d H:i O', '2026-08-10 14:30 +0200', '2026-08-10 12:30:00'],
    'colon offset' => ['Y-m-d H:i P', '2026-08-10 14:30 +02:00', '2026-08-10 12:30:00'],
    'seconds offset' => ['Y-m-d H:i Z', '2026-08-10 14:30 7200', '2026-08-10 12:30:00'],
    'daylight-saving indicator' => ['Y-m-d H:i I', '2026-08-10 14:30 1', '2026-08-10 12:30:00'],
]);

test('sqlite exact decimals have mathematically correct comparison keys', function () {
    $values = [
        '-99999999999999999999999999999999999.000000000000000000000000000001',
        '-10.25',
        '-2',
        '-0.0001',
        '0',
        '0.0001',
        '2',
        '10.25',
        '99999999999999999999999999999999999.000000000000000000000000000001',
    ];

    $sorted = $values;
    usort($sorted, fn (string $left, string $right): int => strcmp(
        ExactDecimal::sortableKey($left),
        ExactDecimal::sortableKey($right),
    ));

    expect($sorted)->toBe($values);

    Schema::create('core_10_exact_decimal_sort', function (Blueprint $table) {
        $table->id();
        $table->text('amount');
    });
    DB::table('core_10_exact_decimal_sort')->insert(array_map(
        fn (string $value): array => ['amount' => $value],
        array_reverse($values),
    ));
    ExactDecimal::registerSqliteFunction(DB::connection());

    expect(DB::table('core_10_exact_decimal_sort')
        ->orderByRaw('aura_decimal_sort_key(amount)')
        ->pluck('amount')->all())->toBe($values)
        ->and(DB::table('core_10_exact_decimal_sort')
            ->whereRaw('aura_decimal_sort_key(amount) > aura_decimal_sort_key(?)', ['2'])
            ->orderByRaw('aura_decimal_sort_key(amount)')
            ->pluck('amount')->all())->toBe(array_slice($values, 7));
});

test('sqlite exact decimal keys reject malformed and over-precision legacy values', function () {
    $valid = [
        '-99999999999999999999999999999999999.000000000000000000000000000001',
        '-0.25',
        '0',
        '0.125',
        str_repeat('9', 65),
    ];
    $invalid = [
        '',
        'not-a-number',
        '1e3',
        str_repeat('9', 66),
        '1.'.str_repeat('2', 65),
    ];

    foreach ($valid as $value) {
        expect(ExactDecimal::sortableKey($value))->toMatch('/^[012]/');
    }

    foreach ($invalid as $value) {
        expect(ExactDecimal::sortableKey($value))->toStartWith('3');
    }

    Schema::create('core_10_invalid_exact_decimals', function (Blueprint $table) {
        $table->id();
        $table->text('amount');
    });
    DB::table('core_10_invalid_exact_decimals')->insert(array_map(
        fn (string $value): array => ['amount' => $value],
        [...$valid, ...$invalid],
    ));
    ExactDecimal::registerSqliteFunction(DB::connection());

    $sorted = DB::table('core_10_invalid_exact_decimals')
        ->orderByRaw("CASE WHEN substr(aura_decimal_sort_key(amount), 1, 1) = '3' THEN 1 ELSE 0 END")
        ->orderByRaw('aura_decimal_sort_key(amount)')
        ->pluck('amount')->all();

    expect(array_slice($sorted, 0, count($valid)))->toBe($valid)
        ->and(array_slice($sorted, count($valid)))->toBe([
            '',
            '1.'.str_repeat('2', 65),
            '1e3',
            str_repeat('9', 66),
            'not-a-number',
        ]);
});

test('invalid configured timezones fail clearly instead of falling back to utc', function () {
    $datetime = new Datetime;
    $field = [
        'slug' => 'occurred_at',
        'storage_timezone' => 'Europe/Zurih',
    ];

    expect(fn () => $datetime->hydrateFromStorage(
        '2026-08-09 16:15:00',
        $field,
        null,
        FieldValueStorage::Meta,
        FieldValueContext::View,
    ))->toThrow(InvalidFieldValue::class, 'Europe/Zurih');
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

test('explicit instants in a storage timezone overlap are rejected before physical or meta persistence', function (
    string $value,
    string $field,
) {
    $rowsBefore = DB::table('core_10_ambiguous_storage_values')->count();
    $metaBefore = DB::table('meta')->count();

    expect(fn () => Core10AmbiguousStorageResource::create([$field => $value]))
        ->toThrow(InvalidFieldValue::class, 'cannot be represented unambiguously')
        ->and(DB::table('core_10_ambiguous_storage_values')->count())->toBe($rowsBefore)
        ->and(DB::table('meta')->count())->toBe($metaBefore);
})->with([
    'summer fold in a physical column' => ['2026-10-25T02:30:00+02:00', 'physical_occurred_at'],
    'winter fold in a physical column' => ['2026-10-25T02:30:00+01:00', 'physical_occurred_at'],
    'summer fold in meta' => ['2026-10-25T02:30:00+02:00', 'meta_occurred_at'],
    'winter fold in meta' => ['2026-10-25T02:30:00+01:00', 'meta_occurred_at'],
]);

test('ambiguous legacy storage values remain raw across hydration and presentation surfaces', function () {
    $resource = Core10AmbiguousStorageResource::create([
        'physical_occurred_at' => '2026-10-25T03:30:00+01:00',
        'meta_occurred_at' => '2026-10-25T03:30:00+01:00',
    ]);
    $legacyValue = '2026-10-25 02:30:00';

    DB::table('core_10_ambiguous_storage_values')
        ->where('id', $resource->id)
        ->update(['physical_occurred_at' => $legacyValue]);
    DB::table('meta')
        ->where('metable_id', $resource->id)
        ->where('key', 'meta_occurred_at')
        ->update(['value' => $legacyValue]);

    $resource = $resource->fresh();

    foreach (['physical_occurred_at', 'meta_occurred_at'] as $slug) {
        expect($resource->resolveFieldValueInContext($slug, FieldValueContext::Model))->toBe($legacyValue)
            ->and($resource->resolveFieldValueInContext($slug, FieldValueContext::Edit))->toBe($legacyValue)
            ->and(trim(strip_tags((string) $resource->displayInContext($slug, FieldValueContext::Index))))->toBe($legacyValue)
            ->and(trim(strip_tags((string) $resource->displayInContext($slug, FieldValueContext::View))))->toBe($legacyValue)
            ->and(trim(strip_tags((string) $resource->exportFieldValue($slug))))->toBe($legacyValue);
    }
});

test('unambiguous explicit instants survive physical and meta storage and every presentation context', function () {
    $resource = Core10AmbiguousStorageResource::create([
        'physical_occurred_at' => '2026-10-25T03:30:00+01:00',
        'meta_occurred_at' => '2026-10-25T03:30:00+01:00',
    ])->refresh();
    $storedValue = '2026-10-25 03:30:00';
    $hydratedValue = '2026-10-24 22:30:00';
    $displayValue = '2026-10-24 22:30 -04:00 EDT';

    expect(DB::table('core_10_ambiguous_storage_values')
        ->where('id', $resource->id)
        ->value('physical_occurred_at'))->toBe($storedValue)
        ->and(DB::table('meta')
            ->where('metable_id', $resource->id)
            ->where('key', 'meta_occurred_at')
            ->value('value'))->toBe($storedValue);

    foreach (['physical_occurred_at', 'meta_occurred_at'] as $slug) {
        expect($resource->resolveFieldValueInContext($slug, FieldValueContext::Model))->toBe($hydratedValue)
            ->and($resource->resolveFieldValueInContext($slug, FieldValueContext::Edit))->toBe($hydratedValue)
            ->and(trim(strip_tags((string) $resource->displayInContext($slug, FieldValueContext::Index))))->toBe($displayValue)
            ->and(trim(strip_tags((string) $resource->displayInContext($slug, FieldValueContext::View))))->toBe($displayValue)
            ->and(trim(strip_tags((string) $resource->exportFieldValue($slug))))->toBe($displayValue);
    }
});

test('null and empty datetimes remain distinct for physical and meta storage adapters', function () {
    $resource = Core10AmbiguousStorageResource::create([
        'physical_occurred_at' => null,
        'meta_occurred_at' => null,
    ])->refresh();
    $datetime = new Datetime;

    expect(DB::table('core_10_ambiguous_storage_values')
        ->where('id', $resource->id)
        ->value('physical_occurred_at'))->toBeNull()
        ->and(DB::table('meta')
            ->where('metable_id', $resource->id)
            ->where('key', 'meta_occurred_at')
            ->value('value'))->toBeNull();

    foreach (Core10AmbiguousStorageResource::getFields() as $field) {
        $storage = $field['slug'] === 'physical_occurred_at'
            ? FieldValueStorage::Physical
            : FieldValueStorage::Meta;

        expect($resource->resolveFieldValueInContext($field['slug'], FieldValueContext::Model))->toBeNull()
            ->and($resource->resolveFieldValueInContext($field['slug'], FieldValueContext::Edit))->toBeNull()
            ->and(trim(strip_tags((string) $resource->displayInContext($field['slug'], FieldValueContext::Index))))->toBe('')
            ->and(trim(strip_tags((string) $resource->displayInContext($field['slug'], FieldValueContext::View))))->toBe('')
            ->and(trim(strip_tags((string) $resource->exportFieldValue($field['slug']))))->toBe('')
            ->and($datetime->normalizeForStorage('', $field, $resource, $storage))->toBe('')
            ->and($datetime->hydrateFromStorage('', $field, $resource, $storage, FieldValueContext::View))->toBe('');
    }
});

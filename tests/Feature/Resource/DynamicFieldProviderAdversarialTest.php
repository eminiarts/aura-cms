<?php

use Aura\Base\BaseResource;
use Aura\Base\ConditionalLogic;
use Aura\Base\Contracts\ContextualFieldProvider;
use Aura\Base\Contracts\FieldProvider;
use Aura\Base\Facades\Aura;
use Aura\Base\FieldProviderContext;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Traits\InputFields;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Core08AdversarialResource extends Resource
{
    public static function getFields(): array
    {
        return [
            ['name' => 'Base', 'slug' => 'base', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class Core08UnrelatedResource extends Resource
{
    public static int $declarationCalls = 0;

    public static function getFields(): array
    {
        static::$declarationCalls++;

        return [
            ['name' => 'Unrelated', 'slug' => 'unrelated', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class Core08MutableInputFieldsConsumer
{
    use InputFields;

    public static array $definition = [
        ['name' => 'First', 'slug' => 'first', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];

    public function getFields(): array
    {
        return static::$definition;
    }
}

class Core08MutableBaseFieldResource extends Resource
{
    public static array $definition = [
        ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];

    public static function getFields(): array
    {
        return static::$definition;
    }
}

class Core08BaseResourceTarget extends BaseResource
{
    public static function getFields(): array
    {
        return [];
    }
}

class Core08WildcardProviderState
{
    public static int $fieldsCalls = 0;

    public static array $resourceClasses = [];

    public static function reset(): void
    {
        static::$fieldsCalls = 0;
        static::$resourceClasses = [];
    }
}

class Core08WildcardProvider implements FieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        Core08WildcardProviderState::$resourceClasses[] = $resourceClass;

        return [];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        Core08WildcardProviderState::$fieldsCalls++;

        return [
            ['name' => 'Dynamic', 'slug' => 'dynamic', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class Core08ConditionalProviderState
{
    public static int $teamId = 1;
}

class Core08ConditionalProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => Core08ConditionalProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        $visible = $context->value('team_id') === 1;

        return [
            [
                'name' => 'Context visibility',
                'slug' => 'context_visibility',
                'type' => 'Aura\\Base\\Fields\\Text',
                'conditional_logic' => static fn (): bool => $visible,
            ],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['context_visibility'];
    }
}

class Core08RoleUser extends Authenticatable
{
    public bool $hasManagerRole = true;

    public function hasRole(string $role): bool
    {
        return $role === 'manager' && $this->hasManagerRole;
    }

    public function isSuperAdmin(): bool
    {
        return false;
    }
}

class Core08RefreshProviderState
{
    public static int $teamId = 1;
}

class Core08RefreshResource extends Resource
{
    public static function getFields(): array
    {
        return [];
    }
}

class Core08RefreshProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => Core08RefreshProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        if ($context->value('team_id') === 1) {
            return [
                ['name' => 'Old', 'slug' => 'old_slug', 'type' => 'Aura\\Base\\Fields\\Text'],
                ['name' => 'Cast', 'slug' => 'cast_value', 'type' => 'Aura\\Base\\Fields\\Boolean'],
            ];
        }

        return [
            ['name' => 'New', 'slug' => 'new_slug', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Cast', 'slug' => 'cast_value', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['old_slug', 'new_slug', 'cast_value'];
    }
}

class Core08AttributeBoundaryProviderState
{
    public static int $contextCalls = 0;

    public static int $fieldsCalls = 0;

    public static int $teamId = 1;

    public static function reset(): void
    {
        static::$contextCalls = 0;
        static::$fieldsCalls = 0;
        static::$teamId = 1;
    }
}

class Core08AttributeBoundaryResource extends Resource
{
    public static function getFields(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'old_count' => 'integer',
            'order' => 'integer',
        ];
    }
}

class Core08HydrationResource extends Resource
{
    public static $customTable = true;

    public static bool $usesMeta = false;

    protected $fillable = ['title', 'order', 'parent_id'];

    protected $table = 'core08_provider_records';

    public static function getFields(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'old_count' => 'integer',
            'order' => 'integer',
        ];
    }
}

class Core08AttributeBoundaryProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        Core08AttributeBoundaryProviderState::$contextCalls++;

        return ['team_id' => Core08AttributeBoundaryProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        Core08AttributeBoundaryProviderState::$fieldsCalls++;

        if ($context->value('team_id') !== 1) {
            return [];
        }

        return [
            ['name' => 'Old secret', 'slug' => 'old_secret', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Old count', 'slug' => 'old_count', 'type' => 'Aura\\Base\\Fields\\Number'],
            ['name' => 'Old relation', 'slug' => 'old_relation', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Old nested secret', 'slug' => 'profile.secret', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['old_secret', 'old_count', 'old_relation', 'profile.secret'];
    }
}

class Core08DatabaseManifestProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => Core08AttributeBoundaryProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return (int) DB::table('core08_provider_catalog')->max('version');
    }

    public function fields(FieldProviderContext $context): array
    {
        if ($context->value('team_id') !== 1) {
            return [];
        }

        return DB::table('core08_provider_catalog')
            ->orderBy('id')
            ->get()
            ->map(fn (object $field): array => [
                'name' => $field->name,
                'slug' => $field->slug,
                'type' => 'Aura\\Base\\Fields\\Text',
            ])
            ->all();
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return DB::table('core08_provider_catalog')
            ->orderBy('id')
            ->pluck('slug')
            ->all();
    }
}

class Core08HostileCast implements CastsAttributes
{
    public static int $getCalls = 0;

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        static::$getCalls++;

        return 'hostile cast secret: '.$value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}

class Core08AppendBoundaryResource extends Core08HydrationResource
{
    public static int $accessorCalls = 0;

    protected $appends = ['old_secret'];

    public function arrayableAppendsForTest(): array
    {
        return $this->getArrayableAppends();
    }

    public function getOldSecretAttribute(mixed $value): string
    {
        static::$accessorCalls++;

        return 'hostile accessor secret: '.$value;
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'old_count' => Core08HostileCast::class,
        ];
    }
}

class Core08BaseFillableProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => Core08AttributeBoundaryProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        if ($context->value('team_id') !== 1) {
            return [];
        }

        return [
            ['name' => 'Managed title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['title'];
    }
}

class Core08TimestampProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => Core08AttributeBoundaryProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        if ($context->value('team_id') !== 1) {
            return [];
        }

        return [
            ['name' => 'Managed update timestamp', 'slug' => 'updated_at', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['updated_at'];
    }
}

class Core08UnsafeContextProvider implements FieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => Core08AttributeBoundaryProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        return [];
    }
}

class Core08IncompleteManifestProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => Core08AttributeBoundaryProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'Undeclared', 'slug' => 'undeclared', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return [];
    }
}

class Core08VersionProviderState
{
    public static int $fieldsCalls = 0;

    public static array $instances = [];

    public static string $label = 'Version one';

    public static int $version = 1;

    public static int $versionCalls = 0;

    public static function reset(): void
    {
        static::$fieldsCalls = 0;
        static::$label = 'Version one';
        static::$version = 1;
        static::$versionCalls = 0;
        static::$instances = [];
    }
}

class Core08VersionProvider implements FieldProvider
{
    private int $fieldsResolved = 0;

    public function cacheContext(string $resourceClass): array
    {
        return [];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        Core08VersionProviderState::$versionCalls++;

        return Core08VersionProviderState::$version;
    }

    public function fields(FieldProviderContext $context): array
    {
        $this->fieldsResolved++;
        Core08VersionProviderState::$fieldsCalls++;
        Core08VersionProviderState::$instances[] = $this;

        return [
            [
                'name' => Core08VersionProviderState::$label.'-'.$this->fieldsResolved,
                'slug' => 'versioned',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }
}

class Core08UserProviderState
{
    public static int $fieldsCalls = 0;

    public static int $userId = 1;
}

class Core08UserProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['user_id' => Core08UserProviderState::$userId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        Core08UserProviderState::$fieldsCalls++;

        return [
            [
                'name' => 'User '.$context->value('user_id'),
                'slug' => 'user_specific',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['user_specific'];
    }
}

function createCore08ProviderRecordsTable(): void
{
    Schema::create('core08_provider_records', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->string('title')->nullable();
        $table->string('old_secret')->nullable();
        $table->integer('old_count')->nullable();
        $table->integer('order')->nullable();
        $table->json('fields')->nullable();
        $table->string('old_catalog_secret')->nullable();
        $table->string('new_catalog_secret')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->timestamps();
    });
}

function createCore08ProviderCatalogTable(): void
{
    Schema::create('core08_provider_catalog', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->unsignedInteger('version');
    });
}

function createCore08OwnedBoundaryResource(array $attributes): Core08AttributeBoundaryResource
{
    $owner = auth()->user() ?? createSuperAdmin();
    $attributes['user_id'] = $owner->getAuthIdentifier();

    if (config('aura.teams')) {
        return Core08AttributeBoundaryResource::createForTeamForSystem(
            $owner->current_team_id,
            $attributes,
            $owner->getConnection(),
        );
    }

    return Core08AttributeBoundaryResource::createForOwnerForSystem(
        $owner->getAuthIdentifier(),
        $attributes,
        $owner->getConnection(),
    );
}

afterEach(function () {
    Schema::dropIfExists('core08_provider_catalog');
    Schema::dropIfExists('core08_provider_records');
});

beforeEach(function () {
    Core08MutableInputFieldsConsumer::$definition = [
        ['name' => 'First', 'slug' => 'first', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];
    Core08MutableBaseFieldResource::$definition = [
        ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];
    Core08UnrelatedResource::$declarationCalls = 0;
    Core08WildcardProviderState::reset();
    Core08ConditionalProviderState::$teamId = 1;
    Core08RefreshProviderState::$teamId = 1;
    Core08AttributeBoundaryProviderState::reset();
    Core08AppendBoundaryResource::$accessorCalls = 0;
    Core08HostileCast::$getCalls = 0;
    Core08VersionProviderState::reset();
    Core08UserProviderState::$fieldsCalls = 0;
    Core08UserProviderState::$userId = 1;
    Resource::flushFieldCache();
});

it('prunes a context field before :dataset reads it on the same instance', function (bool $flush, Closure $read) {
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08AttributeBoundaryResource::class],
    );

    $resource = new Core08AttributeBoundaryResource;
    $resource->forceFill(['old_secret' => 'team A secret']);

    Core08AttributeBoundaryProviderState::$teamId = 2;

    if ($flush) {
        Aura::flushFieldCache();
    }

    $result = $read($resource);

    if (is_array($result)) {
        expect($result)->not->toHaveKey('old_secret')
            ->and((array) ($result['fields'] ?? []))->not->toHaveKey('old_secret');
    } else {
        expect($result)->toBeNull();
    }

    expect($resource->old_secret)->toBeNull()
        ->and($resource->getAttribute('old_secret'))->toBeNull()
        ->and($resource->getAttributeValue('old_secret'))->toBeNull()
        ->and($resource->getAttributeValue('old_count'))->toBeNull()
        ->and((array) $resource->getAttributeValue('fields'))->not->toHaveKey('old_secret')
        ->and($resource->hasAttribute('old_secret'))->toBeFalse()
        ->and($resource->getAttributes())->not->toHaveKey('old_secret')
        ->and($resource->getAttributes())->not->toHaveKey('old_count')
        ->and($resource->getRawOriginal())->not->toHaveKey('old_secret')
        ->and($resource->getOriginal())->not->toHaveKey('old_secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('team A secret')
        ->and($resource->getAttribute('old_secret'))->toBe('team A secret');
})->with([
    'magic without flush' => [false, fn (Core08AttributeBoundaryResource $resource): mixed => $resource->old_secret],
    'magic after flush' => [true, fn (Core08AttributeBoundaryResource $resource): mixed => $resource->old_secret],
    'getAttribute without flush' => [false, fn (Core08AttributeBoundaryResource $resource): mixed => $resource->getAttribute('old_secret')],
    'getAttribute after flush' => [true, fn (Core08AttributeBoundaryResource $resource): mixed => $resource->getAttribute('old_secret')],
    'getAttributeValue without flush' => [false, fn (Core08AttributeBoundaryResource $resource): mixed => $resource->getAttributeValue('old_secret')],
    'getAttributeValue after flush' => [true, fn (Core08AttributeBoundaryResource $resource): mixed => $resource->getAttributeValue('old_secret')],
    'toArray without flush' => [false, fn (Core08AttributeBoundaryResource $resource): array => $resource->toArray()],
    'toArray after flush' => [true, fn (Core08AttributeBoundaryResource $resource): array => $resource->toArray()],
    'JSON without flush' => [false, fn (Core08AttributeBoundaryResource $resource): array => json_decode($resource->toJson(), true, flags: JSON_THROW_ON_ERROR)],
    'JSON after flush' => [true, fn (Core08AttributeBoundaryResource $resource): array => json_decode($resource->toJson(), true, flags: JSON_THROW_ON_ERROR)],
]);

it('preserves physical model state across provider context changes', function (bool $flush) {
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08AttributeBoundaryResource::class],
    );

    $parent = new Core08AttributeBoundaryResource;
    $resource = new Core08AttributeBoundaryResource;
    $resource->forceFill([
        'old_secret' => 'team A secret',
        'title' => 'Original title',
        'order' => '7',
    ]);
    $resource->setRelation('parent', $parent);
    $resource->syncOriginal();
    $resource->title = 'Changed title';

    Core08AttributeBoundaryProviderState::$teamId = 2;

    if ($flush) {
        Aura::flushFieldCache();
    }

    expect($resource->title)->toBe('Changed title')
        ->and($resource->getAttribute('order'))->toBe(7)
        ->and($resource->parent)->toBe($parent)
        ->and($resource->isDirty('title'))->toBeTrue()
        ->and($resource->isDirty('old_secret'))->toBeFalse()
        ->and($resource->exists)->toBeFalse()
        ->and($resource->toArray())->toMatchArray([
            'title' => 'Changed title',
            'order' => 7,
        ])
        ->and(json_decode($resource->toJson(), true, flags: JSON_THROW_ON_ERROR))->toMatchArray([
            'title' => 'Changed title',
            'order' => 7,
        ]);

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->fieldBySlug('old_secret'))->not->toBeNull()
        ->and($resource->old_secret)->toBe('team A secret')
        ->and($resource->getAttribute('old_secret'))->toBe('team A secret')
        ->and($resource->getRawOriginal('old_secret'))->toBe('team A secret')
        ->and($resource->title)->toBe('Changed title')
        ->and($resource->parent)->toBe($parent)
        ->and(Core08AttributeBoundaryProviderState::$fieldsCalls)->toBe($flush ? 3 : 2);
})->with([
    'without flush' => false,
    'after flush' => true,
]);

it('requires contextual providers to declare a stable managed slug manifest', function () {
    Aura::registerFieldProvider(
        Core08UnsafeContextProvider::class,
        resources: [Core08AttributeBoundaryResource::class],
    );

    expect(fn () => new Core08AttributeBoundaryResource)
        ->toThrow(InvalidArgumentException::class, ContextualFieldProvider::class);
});

it('rejects contextual fields outside the declared managed slug manifest', function () {
    Aura::registerFieldProvider(
        Core08IncompleteManifestProvider::class,
        resources: [Core08AttributeBoundaryResource::class],
    );

    expect(fn () => new Core08AttributeBoundaryResource)
        ->toThrow(InvalidArgumentException::class, 'undeclared');
});

it('isolates provider-managed base fillable columns on fresh inactive hydration and save', function () {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'base fillable secret',
        'order' => 7,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Core08AttributeBoundaryProviderState::$teamId = 2;
    Aura::registerFieldProvider(
        Core08BaseFillableProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();

    expect($resource->title)->toBeNull()
        ->and($resource->getAttribute('title'))->toBeNull()
        ->and($resource->getAttributeValue('title'))->toBeNull()
        ->and($resource->hasAttribute('title'))->toBeFalse()
        ->and($resource->getAttributes())->not->toHaveKey('title')
        ->and($resource->getRawOriginal())->not->toHaveKey('title')
        ->and($resource->toArray())->not->toHaveKey('title')
        ->and($resource->toJson())->not->toContain('base fillable secret');

    $resource->order = 8;

    expect($resource->save())->toBeTrue()
        ->and(DB::table($resource->getTable())->where('id', 1)->value('title'))->toBe('base fillable secret')
        ->and(DB::table($resource->getTable())->where('id', 1)->value('order'))->toBe(8);

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->title)->toBe('base fillable secret');

    Core08AttributeBoundaryProviderState::$teamId = 2;
    $resource->title = null;

    expect($resource->title)->toBeNull()
        ->and($resource->save())->toBeTrue()
        ->and(DB::table($resource->getTable())->where('id', 1)->value('title'))->toBe('base fillable secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->title)->toBeNull()
        ->and($resource->getRawOriginal('title'))->toBe('base fillable secret')
        ->and($resource->isDirty('title'))->toBeTrue();
});

it('rejects contextual BaseResource and wildcard targets without changing static provider support', function () {
    expect(fn () => Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08BaseResourceTarget::class],
    ))->toThrow(InvalidArgumentException::class, BaseResource::class);

    expect(fn () => Aura::registerFieldProvider(Core08AttributeBoundaryProvider::class))
        ->toThrow(InvalidArgumentException::class, 'wildcard');

    Aura::registerFieldProvider(
        Core08WildcardProvider::class,
        resources: [Core08BaseResourceTarget::class],
    );

    expect((new Core08BaseResourceTarget)->fieldsCollection()->pluck('slug')->all())
        ->toBe(['dynamic']);
});

it('hides provider-managed columns on models first hydrated in an inactive context', function (Closure $hydrate) {
    Core08AttributeBoundaryProviderState::$teamId = 2;
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = $hydrate();

    expect($resource->exists)->toBeTrue()
        ->and($resource->old_secret)->toBeNull()
        ->and($resource->getAttribute('old_secret'))->toBeNull()
        ->and($resource->getAttributeValue('old_secret'))->toBeNull()
        ->and($resource->hasAttribute('old_secret'))->toBeFalse()
        ->and($resource->getAttributes())->not->toHaveKey('old_secret')
        ->and($resource->getRawOriginal('old_secret'))->toBeNull()
        ->and($resource->getRawOriginal())->not->toHaveKey('old_secret')
        ->and((array) $resource->getRawOriginal('fields'))->not->toHaveKey('old_secret')
        ->and($resource->getOriginal('old_secret'))->toBeNull()
        ->and($resource->getOriginal())->not->toHaveKey('old_secret')
        ->and((array) $resource->getOriginal('fields'))->not->toHaveKey('old_secret')
        ->and($resource->toArray())->not->toHaveKey('old_secret')
        ->and(json_decode($resource->toJson(), true, flags: JSON_THROW_ON_ERROR))->not->toHaveKey('old_secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('persisted A secret')
        ->and($resource->getAttributeValue('old_secret'))->toBe('persisted A secret')
        ->and($resource->getAttributeValue('old_count'))->toBe(9)
        ->and($resource->hasAttribute('old_secret'))->toBeTrue()
        ->and($resource->getAttributes())->toHaveKey('old_secret', 'persisted A secret')
        ->and($resource->getRawOriginal('old_secret'))->toBe('persisted A secret')
        ->and($resource->getOriginal('old_secret'))->toBe('persisted A secret')
        ->and($resource->toArray())->toHaveKey('old_secret', 'persisted A secret');
})->with([
    'newFromBuilder' => function (): Core08HydrationResource {
        return (new Core08HydrationResource)->newFromBuilder([
            'id' => 10,
            'title' => 'Hydrated directly',
            'old_secret' => 'persisted A secret',
            'old_count' => 9,
            'fields' => ['old_secret' => 'nested A secret'],
            'order' => 7,
        ]);
    },
    'database query' => function (): Core08HydrationResource {
        createCore08ProviderRecordsTable();
        DB::table((new Core08HydrationResource)->getTable())->insert([
            'title' => 'Hydrated from database',
            'old_secret' => 'persisted A secret',
            'old_count' => 9,
            'order' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    },
]);

it('restores physical state exactly after saving another field in an inactive context', function () {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        [
            'id' => 1,
            'title' => 'Parent',
            'old_secret' => 'parent secret',
            'old_count' => 3,
            'order' => 3,
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'title' => 'Original title',
            'old_secret' => 'persisted A secret',
            'old_count' => 9,
            'order' => 7,
            'parent_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $parent = Core08HydrationResource::withoutGlobalScopes()->findOrFail(1);
    $resource = Core08HydrationResource::withoutGlobalScopes()->findOrFail(2);
    $resource->setRelation('parent', $parent);
    $resource->setRelation('old_relation', $parent);
    $resource->forceFill([
        'title' => 'Saved in B',
        'old_secret' => 'pending A secret',
    ]);
    $resource->syncChanges();

    expect($resource->getDirty())->toHaveKey('old_secret', 'pending A secret')
        ->and($resource->getChanges())->toHaveKey('old_secret', 'pending A secret')
        ->and($resource->getPrevious())->toHaveKey('old_secret', 'persisted A secret');

    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($resource->old_secret)->toBeNull()
        ->and($resource->getAttributes())->not->toHaveKey('old_secret')
        ->and($resource->getRawOriginal())->not->toHaveKey('old_secret')
        ->and($resource->getDirty())->not->toHaveKey('old_secret')
        ->and($resource->getChanges())->not->toHaveKey('old_secret')
        ->and($resource->getPrevious())->not->toHaveKey('old_secret')
        ->and($resource->parent)->toBe($parent)
        ->and($resource->old_relation)->toBeNull()
        ->and($parent->old_secret)->toBeNull()
        ->and($resource->getAttribute('old_count'))->toBeNull()
        ->and($resource->getAttribute('order'))->toBe(7)
        ->and($resource->toArray())->not->toHaveKey('old_relation');

    expect($resource->save())->toBeTrue()
        ->and(DB::table($resource->getTable())->where('id', 2)->value('old_secret'))->toBe('persisted A secret')
        ->and(DB::table($resource->getTable())->where('id', 2)->value('title'))->toBe('Saved in B');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('pending A secret')
        ->and($resource->getRawOriginal('old_secret'))->toBe('persisted A secret')
        ->and($resource->getOriginal('old_secret'))->toBe('persisted A secret')
        ->and($resource->getDirty())->toHaveKey('old_secret', 'pending A secret')
        ->and($resource->getChanges())->toHaveKey('old_secret', 'pending A secret')
        ->and($resource->getPrevious())->toHaveKey('old_secret', 'persisted A secret')
        ->and($resource->getAttribute('old_count'))->toBe(9)
        ->and($resource->getAttribute('order'))->toBe(7)
        ->and($resource->parent)->toBe($parent)
        ->and($resource->old_relation)->toBe($parent)
        ->and($parent->old_secret)->toBe('parent secret')
        ->and($resource->toArray())->toHaveKey('old_secret', 'pending A secret')
        ->and(json_decode($resource->toJson(), true, flags: JSON_THROW_ON_ERROR))->toHaveKey('old_secret', 'pending A secret');
});

it('keeps inactive meta values hidden and does not persist queued A changes while saving in B', function () {
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08AttributeBoundaryResource::class],
    );

    if (config('aura.teams')) {
        expect(fn () => Core08AttributeBoundaryResource::create(['title' => 'Ownerless meta resource']))
            ->toThrow(LogicException::class);
    }

    $resource = createCore08OwnedBoundaryResource(['title' => 'Meta resource']);
    $resource->meta()->create(['key' => 'old_secret', 'value' => 'persisted meta secret']);
    $resource->load('meta');

    expect($resource->old_secret)->toBe('persisted meta secret')
        ->and($resource->getMeta('old_secret'))->toBe('persisted meta secret');

    $resource->saveMetaField(['old_secret' => 'queued A secret']);
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($resource->metaFields)->not->toHaveKey('old_secret')
        ->and($resource->old_secret)->toBeNull()
        ->and($resource->getMeta('old_secret'))->toBeNull()
        ->and($resource->toArray())->not->toHaveKey('old_secret')
        ->and(json_decode($resource->toJson(), true, flags: JSON_THROW_ON_ERROR))->not->toHaveKey('old_secret');

    $resource->title = 'Saved in B';

    expect($resource->save())->toBeTrue()
        ->and($resource->meta()->where('key', 'old_secret')->value('value'))->toBe('persisted meta secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('persisted meta secret')
        ->and($resource->getMeta('old_secret'))->toBe('persisted meta secret')
        ->and($resource->metaFields)->toHaveKey('old_secret', 'queued A secret');
});

it('physically quarantines inactive loaded relations and meta across lifecycle operations', function () {
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08AttributeBoundaryResource::class],
    );

    $related = createCore08OwnedBoundaryResource(['title' => 'persisted relation']);
    $resource = createCore08OwnedBoundaryResource(['title' => 'relation owner']);
    $resource->meta()->create(['key' => 'old_secret', 'value' => 'persisted meta relation secret']);
    $resource->meta()->create(['key' => 'active_meta', 'value' => 'active meta value']);
    $resource->load('meta');
    $metaRelation = $resource->getRelation('meta');
    $resource->setRelation('old_relation', $related);
    $resource->saveMetaField(['old_secret' => 'queued meta secret']);
    $related->title = 'dirty hidden relation';

    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($resource->getRelations())->not->toHaveKey('old_relation')
        ->and($resource->getRelation('old_relation'))->toBeNull()
        ->and($resource->getRelationValue('old_relation'))->toBeNull()
        ->and($resource->relationLoaded('old_relation'))->toBeFalse()
        ->and($resource->getRelation('meta')->pluck('key')->all())->toBe(['active_meta'])
        ->and($resource->getRelationValue('meta')->pluck('key')->all())->toBe(['active_meta'])
        ->and($resource->metaFields)->not->toHaveKey('old_secret');

    $clone = clone $resource;
    $replica = $resource->replicate();

    expect($clone->getRelations())->not->toHaveKey('old_relation')
        ->and($clone->getRelation('meta')->pluck('key')->all())->toBe(['active_meta'])
        ->and($replica->getRelations())->not->toHaveKey('old_relation')
        ->and($replica->getRelation('meta')->pluck('key')->all())->toBe(['active_meta']);

    expect($resource->push())->toBeTrue()
        ->and($related->newQuery()->whereKey($related->getKey())->value('title'))->toBe('persisted relation')
        ->and($resource->meta()->where('key', 'old_secret')->value('value'))->toBe('persisted meta relation secret')
        ->and($resource->pushQuietly())->toBeTrue()
        ->and($related->newQuery()->whereKey($related->getKey())->value('title'))->toBe('persisted relation')
        ->and($resource->meta()->where('key', 'old_secret')->value('value'))->toBe('persisted meta relation secret')
        ->and(fn () => $resource->refresh())->not->toThrow(Throwable::class)
        ->and($resource->getRelations())->not->toHaveKey('old_relation')
        ->and($resource->getRelation('meta')->pluck('key')->all())->toBe(['active_meta']);

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->getRelation('old_relation'))->toBe($related)
        ->and($resource->relationLoaded('old_relation'))->toBeTrue()
        ->and($resource->getRelation('meta'))->toBe($metaRelation)
        ->and($resource->metaFields)->toHaveKey('old_secret', 'queued meta secret')
        ->and($clone->getRelation('old_relation'))->toBe($related)
        ->and($replica->getRelations())->not->toHaveKey('old_relation');
});

it('omits inactive state from native serialization while preserving an active round trip', function () {
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $related = (new Core08HydrationResource)->newFromBuilder([
        'id' => 1,
        'title' => 'serialized relation secret',
    ]);
    $resource = (new Core08HydrationResource)->newFromBuilder([
        'id' => 2,
        'title' => 'owner',
        'old_secret' => 'serialized attribute secret',
        'fields' => [
            'old_secret' => 'serialized nested secret',
            'profile' => ['secret' => 'serialized dotted secret'],
        ],
    ]);
    $resource->setRelation('old_relation', $related);
    $resource->metaFields['old_secret'] = 'serialized queue secret';

    Core08AttributeBoundaryProviderState::$teamId = 2;

    $serializedInB = serialize($resource);

    expect($resource->__sleep())->not->toContain('quarantinedProviderFieldState')
        ->and($serializedInB)
        ->not->toContain('serialized attribute secret')
        ->not->toContain('serialized nested secret')
        ->not->toContain('serialized dotted secret')
        ->not->toContain('serialized relation secret')
        ->not->toContain('serialized queue secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('serialized attribute secret')
        ->and($resource->getRelation('old_relation'))->toBe($related)
        ->and($resource->metaFields)->toHaveKey('old_secret', 'serialized queue secret');

    $roundTrip = unserialize(serialize($resource));

    expect($roundTrip)->toBeInstanceOf(Core08HydrationResource::class)
        ->and($roundTrip->old_secret)->toBe('serialized attribute secret')
        ->and($roundTrip->getRelation('old_relation')->title)->toBe('serialized relation secret')
        ->and($roundTrip->metaFields)->toHaveKey('old_secret', 'serialized queue secret');
});

it('restores nested fields state snapshots exactly after an inactive save', function () {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'nested owner',
        'order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    $resource->forceFill(['fields' => [
        'old_secret' => 'original direct secret',
        'profile' => ['secret' => 'original dotted secret', 'active' => 'original active'],
    ]]);
    $resource->syncOriginal();
    $resource->forceFill(['fields' => [
        'old_secret' => 'pending direct secret',
        'profile' => ['secret' => 'pending dotted secret', 'active' => 'pending active'],
    ]]);
    $resource->syncChanges();

    $snapshots = [
        'attributes' => $resource->getAttributes()['fields'],
        'original' => $resource->getRawOriginal('fields'),
        'changes' => $resource->getChanges()['fields'],
        'previous' => $resource->getPrevious()['fields'],
        'dirty' => $resource->getDirty()['fields'],
    ];

    Core08AttributeBoundaryProviderState::$teamId = 2;

    foreach ([$resource->getAttributes(), $resource->getRawOriginal(), $resource->getChanges(), $resource->getPrevious(), $resource->getDirty()] as $state) {
        expect($state['fields'] ?? [])->not->toHaveKey('old_secret')
            ->and($state['fields']['profile'] ?? [])->not->toHaveKey('secret');
    }

    $resource->order = 2;

    expect($resource->save())->toBeTrue();

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->getAttributes()['fields'])->toBe($snapshots['attributes'])
        ->and($resource->getRawOriginal('fields'))->toBe($snapshots['original'])
        ->and($resource->getChanges()['fields'])->toBe($snapshots['changes'])
        ->and($resource->getPrevious()['fields'])->toBe($snapshots['previous'])
        ->and($resource->getDirty()['fields'])->toBe($snapshots['dirty']);
});

it('keeps new refresh delete and clone lifecycle operations isolated', function () {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        [
            'id' => 1,
            'title' => 'Clone source',
            'old_secret' => 'source secret',
            'old_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'title' => 'Delete target',
            'old_secret' => 'delete secret',
            'old_count' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $source = Core08HydrationResource::withoutGlobalScopes()->findOrFail(1);
    $clone = clone $source;
    $clone->old_secret = 'clone-only secret';

    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($source->old_secret)->toBeNull()
        ->and($clone->old_secret)->toBeNull();

    $replica = $source->replicate();

    expect($replica->exists)->toBeFalse()
        ->and($replica->old_secret)->toBeNull();

    $ownerless = new Core08HydrationResource;
    $ownerless->forceFill(['title' => 'Ownerless B']);

    if (config('aura.teams')) {
        expect(fn () => $ownerless->save())
            ->toThrow(LogicException::class);
    }

    createSuperAdmin();
    $new = new Core08HydrationResource;
    $new->forceFill(['title' => 'Created in B', 'old_secret' => 'never persisted']);

    expect($new->old_secret)->toBeNull()
        ->and($new->save())->toBeTrue()
        ->and(DB::table($new->getTable())->where('id', $new->getKey())->value('old_secret'))->toBeNull();

    $delete = Core08HydrationResource::withoutGlobalScopes()->findOrFail(2);

    expect($delete->delete())->toBeTrue()
        ->and(DB::table($delete->getTable())->where('id', 2)->exists())->toBeFalse();

    $new->refresh();
    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($source->old_secret)->toBe('source secret')
        ->and($clone->old_secret)->toBe('clone-only secret')
        ->and($replica->old_secret)->toBeNull()
        ->and($new->old_secret)->toBeNull()
        ->and($new->isDirty('old_secret'))->toBeFalse();
});

it('keeps no-provider attribute reads query-free and declarative', function () {
    config(['aura.features.legacy_fields_append' => false]);

    $parent = new Core08UnrelatedResource;
    $resource = new Core08UnrelatedResource;
    $resource->forceFill(['title' => 'Original title', 'order' => 3]);
    $resource->setRelation('parent', $parent);
    $resource->syncOriginal();
    $resource->title = 'Changed title';

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($resource->title)->toBe('Changed title')
        ->and($resource->getAttribute('order'))->toBe(3)
        ->and($resource->parent)->toBe($parent)
        ->and($resource->isDirty('title'))->toBeTrue()
        ->and($resource->getRawOriginal('title'))->toBe('Original title')
        ->and($resource->getOriginal('title'))->toBe('Original title')
        ->and($resource->getDirty())->toHaveKey('title', 'Changed title')
        ->and($resource->toArray())->toMatchArray(['title' => 'Changed title', 'order' => 3])
        ->and(json_decode($resource->toJson(), true, flags: JSON_THROW_ON_ERROR))->toMatchArray(['title' => 'Changed title', 'order' => 3])
        ->and(Core08UnrelatedResource::$declarationCalls)->toBe(1)
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('limits wildcard providers to Aura resources', function () {
    Aura::registerFieldProvider(Core08WildcardProvider::class);

    expect((new Core08AdversarialResource)->fieldsCollection()->pluck('slug')->all())
        ->toBe(['base', 'dynamic'])
        ->and((new Text)->fieldsCollection()->pluck('slug'))->not->toContain('dynamic')
        ->and(array_values(array_unique(Core08WildcardProviderState::$resourceClasses)))
        ->toBe([Core08AdversarialResource::class]);
});

it('invalidates InputFields caches outside Resource hierarchies', function () {
    $consumer = new Core08MutableInputFieldsConsumer;
    $containerKey = Core08MutableInputFieldsConsumer::class.'-getFieldsBeforeTree';

    expect($consumer->fieldsCollection()->pluck('slug')->all())->toBe(['first'])
        ->and($consumer->getFieldsBeforeTree()->pluck('slug')->all())->toBe(['first'])
        ->and(app()->bound($containerKey))->toBeTrue();

    Core08MutableInputFieldsConsumer::$definition = [
        ['name' => 'Second', 'slug' => 'second', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];

    Aura::flushFieldCache();

    expect(app()->bound($containerKey))->toBeFalse()
        ->and($consumer->fieldsCollection()->pluck('slug')->all())->toBe(['second'])
        ->and($consumer->getFieldsBeforeTree()->pluck('slug')->all())->toBe(['second']);
});

it('preserves base fillable attributes when a mutable definition removes one', function () {
    $resource = new Core08MutableBaseFieldResource;
    $resource->forceFill(['title' => 'Pending title']);

    expect($resource->inputFieldsSlugs())->toContain('title')
        ->and($resource->getFillable())->toContain('title');

    Core08MutableBaseFieldResource::$definition = [];
    Aura::flushFieldCache();

    expect($resource->inputFieldsSlugs())->not->toContain('title')
        ->and($resource->getFillable())->toContain('title')
        ->and($resource->getAttribute('title'))->toBe('Pending title');
});

it('does not collapse closure conditions across provider contexts', function () {
    Aura::registerFieldProvider(
        Core08ConditionalProvider::class,
        resources: [Core08AdversarialResource::class],
    );

    $resource = new Core08AdversarialResource;
    $teamOneField = $resource->fieldBySlug('context_visibility');

    expect(ConditionalLogic::shouldDisplayField($resource, $teamOneField, ['fields' => []]))->toBeTrue();

    Core08ConditionalProviderState::$teamId = 2;
    $teamTwoField = $resource->fieldBySlug('context_visibility');

    expect(ConditionalLogic::shouldDisplayField($resource, $teamTwoField, ['fields' => []]))->toBeFalse();
});

it('clears conditional decisions with field caches', function () {
    $visible = true;
    $resource = new Core08AdversarialResource;
    $field = [
        'slug' => 'closure_visibility',
        'conditional_logic' => function () use (&$visible): bool {
            return $visible;
        },
    ];

    expect(ConditionalLogic::shouldDisplayField($resource, $field, ['fields' => []]))->toBeTrue();

    $visible = false;
    Aura::flushFieldCache();

    expect(ConditionalLogic::shouldDisplayField($resource, $field, ['fields' => []]))->toBeFalse();
});

it('does not cache role visibility across role changes for the same user', function () {
    $user = new Core08RoleUser;
    $user->id = 9001;
    Auth::setUser($user);

    $field = [
        'slug' => 'manager_only',
        'conditional_logic' => [
            ['field' => 'role', 'operator' => '==', 'value' => 'manager'],
        ],
    ];
    $resource = new Core08AdversarialResource;

    expect(ConditionalLogic::shouldDisplayField($resource, $field, ['fields' => []]))->toBeTrue();

    $user->hasManagerRole = false;

    expect(ConditionalLogic::shouldDisplayField($resource, $field, ['fields' => []]))->toBeFalse();
});

it('refreshes all definition-derived state on an existing Resource instance', function () {
    Aura::registerFieldProvider(
        Core08RefreshProvider::class,
        resources: [Core08RefreshResource::class],
    );

    $resource = new Core08RefreshResource;
    $resource->setRelation('meta', collect([
        (object) ['key' => 'old_slug', 'value' => 'old value'],
        (object) ['key' => 'new_slug', 'value' => 'new value'],
        (object) ['key' => 'cast_value', 'value' => '0'],
    ]));
    $resource->forceFill(['old_slug' => 'pending old value']);
    $resource->metaFields['old_slug'] = 'queued old value';
    $resource->setTableDisplayValue('old_slug', 'cached display');

    expect($resource->getFillable())->not->toContain('old_slug', 'new_slug', 'cast_value')
        ->and($resource->fields->keys()->all())->toBe(['old_slug', 'cast_value'])
        ->and($resource->getMeta('cast_value'))->toBeFalse()
        ->and($resource->hasTableDisplayValue('old_slug'))->toBeTrue();

    Core08RefreshProviderState::$teamId = 2;
    Aura::flushFieldCache();

    expect($resource->getFillable())->not->toContain('old_slug', 'new_slug', 'cast_value')
        ->and($resource->getAttributes())->not->toHaveKey('old_slug')
        ->and($resource->metaFields)->not->toHaveKey('old_slug')
        ->and($resource->fields->keys()->all())->toBe(['new_slug', 'cast_value'])
        ->and($resource->getMeta('cast_value'))->toBe('0')
        ->and($resource->hasTableDisplayValue('old_slug'))->toBeFalse();

    Core08RefreshProviderState::$teamId = 1;

    expect($resource->old_slug)->toBe('pending old value')
        ->and($resource->metaFields)->toHaveKey('old_slug', 'queued old value');
});

it('uses cache versions at an explicit refresh boundary without repeated field queries', function () {
    Aura::registerFieldProvider(
        Core08VersionProvider::class,
        resources: [Core08AdversarialResource::class],
    );

    $resource = new Core08AdversarialResource;

    expect($resource->fieldBySlug('versioned')['name'])->toBe('Version one-1')
        ->and($resource->fieldsCollection()->last()['name'])->toBe('Version one-1')
        ->and(Core08VersionProviderState::$versionCalls)->toBe(1)
        ->and(Core08VersionProviderState::$fieldsCalls)->toBe(1);

    Core08VersionProviderState::$label = 'Ignored without a version change';
    Aura::refreshFieldProviderVersions();

    expect($resource->fieldBySlug('versioned')['name'])->toBe('Version one-1')
        ->and(Core08VersionProviderState::$versionCalls)->toBe(2)
        ->and(Core08VersionProviderState::$fieldsCalls)->toBe(1);

    Core08VersionProviderState::$version = 2;
    Core08VersionProviderState::$label = 'Version two';
    Aura::refreshFieldProviderVersions();

    expect($resource->fieldBySlug('versioned')['name'])->toBe('Version two-1')
        ->and(Core08VersionProviderState::$versionCalls)->toBe(3)
        ->and(Core08VersionProviderState::$fieldsCalls)->toBe(2);
});

it('recomputes a growing database manifest across the :invalidation boundary', function (string $invalidation) {
    createCore08ProviderRecordsTable();
    createCore08ProviderCatalogTable();
    DB::table('core08_provider_catalog')->insert([
        'name' => 'Old catalog field',
        'slug' => 'old_catalog_secret',
        'version' => 1,
    ]);
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'catalog owner',
        'old_catalog_secret' => 'old catalog secret',
        'new_catalog_secret' => 'new catalog secret',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Aura::registerFieldProvider(
        Core08DatabaseManifestProvider::class,
        resources: [Core08HydrationResource::class],
    );
    Aura::captureBaselineState();

    $firstWorkerModel = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    $secondWorkerModel = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();

    expect($firstWorkerModel->fieldsCollection()->pluck('slug')->all())
        ->toBe(['old_catalog_secret']);

    DB::table('core08_provider_catalog')->insert([
        'name' => 'New catalog field',
        'slug' => 'new_catalog_secret',
        'version' => 2,
    ]);

    match ($invalidation) {
        'version refresh' => Aura::refreshFieldProviderVersions(),
        'full field flush' => Aura::flushFieldCache(),
        'worker state flush' => Aura::flushState(),
    };

    expect($firstWorkerModel->fieldsCollection()->pluck('slug')->all())
        ->toBe(['old_catalog_secret', 'new_catalog_secret'])
        ->and($secondWorkerModel->fieldsCollection()->pluck('slug')->all())
        ->toBe(['old_catalog_secret', 'new_catalog_secret']);

    Core08AttributeBoundaryProviderState::$teamId = 2;

    foreach ([$firstWorkerModel, $secondWorkerModel] as $workerModel) {
        expect($workerModel->getAttributes())
            ->not->toHaveKey('old_catalog_secret')
            ->not->toHaveKey('new_catalog_secret');
    }

    $freshInactiveModel = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();

    expect($freshInactiveModel->getAttributes())
        ->not->toHaveKey('old_catalog_secret')
        ->not->toHaveKey('new_catalog_secret');
})->with([
    'version refresh' => 'version refresh',
    'full field flush' => 'full field flush',
    'worker state flush' => 'worker state flush',
]);

it('filters static and dynamic inactive appends before hostile accessors and casts run', function () {
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08AppendBoundaryResource::class],
    );

    $resource = (new Core08AppendBoundaryResource)->newFromBuilder([
        'id' => 1,
        'title' => 'append owner',
        'old_secret' => 'raw accessor value',
        'old_count' => 7,
    ]);
    $resource->append('old_count');

    expect($resource->getAppends())->toBe(['old_secret', 'old_count'])
        ->and($resource->arrayableAppendsForTest())->toBe([
            'old_secret' => 'old_secret',
            'old_count' => 'old_count',
        ])
        ->and($resource->attributesToArray())
        ->toHaveKey('old_secret', 'hostile accessor secret: raw accessor value')
        ->toHaveKey('old_count', 'hostile cast secret: 7');

    Core08AppendBoundaryResource::$accessorCalls = 0;
    Core08HostileCast::$getCalls = 0;
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($resource->getAppends())->toBe([])
        ->and($resource->arrayableAppendsForTest())->toBe([])
        ->and($resource->attributesToArray())
        ->not->toHaveKey('old_secret')
        ->not->toHaveKey('old_count')
        ->and($resource->toArray())
        ->not->toHaveKey('old_secret')
        ->not->toHaveKey('old_count')
        ->and($resource->toJson())
        ->not->toContain('hostile accessor secret')
        ->not->toContain('hostile cast secret')
        ->and(Core08AppendBoundaryResource::$accessorCalls)->toBe(0)
        ->and(Core08HostileCast::$getCalls)->toBe(0);

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->getAppends())->toBe(['old_secret', 'old_count'])
        ->and($resource->attributesToArray())
        ->toHaveKey('old_secret', 'hostile accessor secret: raw accessor value')
        ->toHaveKey('old_count', 'hostile cast secret: 7');
});

it('blocks inactive atomic primary writes through :method', function (string $method, array $parameters) {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'atomic owner',
        'old_secret' => 'persisted secret',
        'old_count' => 10,
        'order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    $resource->old_secret = 'pending active secret';
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect(fn () => $resource->{$method}(...$parameters))
        ->toThrow(LogicException::class, 'inactive provider-managed field [old_count]')
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_count'))->toBe(10)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_secret'))->toBe('persisted secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('pending active secret')
        ->and($resource->getRawOriginal('old_secret'))->toBe('persisted secret')
        ->and($resource->isDirty('old_secret'))->toBeTrue()
        ->and($resource->old_count)->toBe(10);
})->with([
    'increment' => ['increment', ['old_count', 2]],
    'decrement' => ['decrement', ['old_count', 2]],
    'increment quietly' => ['incrementQuietly', ['old_count', 2]],
    'decrement quietly' => ['decrementQuietly', ['old_count', 2]],
    'increment each' => ['incrementEach', [['old_count' => 2]]],
    'decrement each' => ['decrementEach', [['old_count' => 2]]],
    'increment each quietly' => ['incrementEachQuietly', [['old_count' => 2]]],
    'decrement each quietly' => ['decrementEachQuietly', [['old_count' => 2]]],
    'increment or fail' => ['incrementOrFail', ['old_count', 2]],
    'decrement or fail' => ['decrementOrFail', ['old_count', 2]],
]);

it('blocks inactive atomic extra columns without disturbing active state', function (string $method, array $parameters) {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'atomic extra owner',
        'old_secret' => 'persisted secret',
        'old_count' => 10,
        'order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    $resource->old_secret = 'pending active secret';
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect(fn () => $resource->{$method}(...$parameters))
        ->toThrow(LogicException::class, 'inactive provider-managed field')
        ->and(DB::table($resource->getTable())->where('id', 1)->value('order'))->toBe(2)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_secret'))->toBe('persisted secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('pending active secret')
        ->and($resource->getRawOriginal('old_secret'))->toBe('persisted secret')
        ->and($resource->isDirty('old_secret'))->toBeTrue();
})->with([
    'increment' => ['increment', ['order', 2, ['old_secret' => 'atomic extra attack']]],
    'decrement' => ['decrement', ['order', 2, ['old_secret' => 'atomic extra attack']]],
    'increment quietly' => ['incrementQuietly', ['order', 2, ['old_secret' => 'atomic extra attack']]],
    'decrement quietly' => ['decrementQuietly', ['order', 2, ['old_secret' => 'atomic extra attack']]],
    'increment each' => ['incrementEach', [['order' => 2], ['old_secret' => 'atomic extra attack']]],
    'decrement each' => ['decrementEach', [['order' => 2], ['old_secret' => 'atomic extra attack']]],
    'increment each quietly' => ['incrementEachQuietly', [['order' => 2], ['old_secret' => 'atomic extra attack']]],
    'decrement each quietly' => ['decrementEachQuietly', [['order' => 2], ['old_secret' => 'atomic extra attack']]],
    'nested fields extra' => ['increment', ['order', 2, ['fields' => ['old_secret' => 'nested atomic extra attack']]]],
    'json selector extra' => ['increment', ['order', 2, ['fields->profile->secret' => 'selector atomic extra attack']]],
    'increment or fail' => ['incrementOrFail', ['order', 2, ['old_secret' => 'atomic extra attack']]],
    'decrement or fail' => ['decrementOrFail', ['order', 2, ['old_secret' => 'atomic extra attack']]],
]);

it('allows active and nonmanaged atomic updates while restoring hidden state exactly', function () {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'allowed atomic owner',
        'old_secret' => 'persisted secret',
        'old_count' => 10,
        'order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();

    expect($resource->incrementOrFail('old_count', 2))->toBe(1)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_count'))->toBe(12);

    $resource->old_secret = 'pending active secret';
    $resource->syncChanges();
    $hiddenState = [
        'attribute' => $resource->getAttributes()['old_secret'],
        'original' => $resource->getRawOriginal('old_secret'),
        'change' => $resource->getChanges()['old_secret'],
        'previous_present' => array_key_exists('old_secret', $resource->getPrevious()),
        'previous' => $resource->getPrevious()['old_secret'] ?? null,
        'dirty' => $resource->getDirty()['old_secret'],
    ];
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($resource->increment('order', 3))->toBe(1)
        ->and($resource->decrementQuietly('order', 1))->toBe(1)
        ->and($resource->incrementEach(['order' => 2]))->toBe(1)
        ->and($resource->incrementOrFail('order', 1))->toBe(1)
        ->and($resource->decrementOrFail('order', 1))->toBe(1)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('order'))->toBe(6)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_secret'))->toBe('persisted secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('pending active secret')
        ->and($resource->getAttributes()['old_secret'])->toBe($hiddenState['attribute'])
        ->and($resource->getRawOriginal('old_secret'))->toBe($hiddenState['original'])
        ->and($resource->getChanges()['old_secret'])->toBe($hiddenState['change'])
        ->and(array_key_exists('old_secret', $resource->getPrevious()))->toBe($hiddenState['previous_present'])
        ->and($resource->getPrevious()['old_secret'] ?? null)->toBe($hiddenState['previous'])
        ->and($resource->getDirty()['old_secret'])->toBe($hiddenState['dirty'])
        ->and($resource->old_count)->toBe(12);
});

it('suppresses implicit timestamp extras when atomic nonmanaged writes run', function () {
    createCore08ProviderRecordsTable();
    $persistedTimestamp = now()->subDay()->startOfSecond();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'timestamp owner',
        'old_count' => 10,
        'order' => 1,
        'created_at' => $persistedTimestamp,
        'updated_at' => $persistedTimestamp,
    ]);
    Aura::registerFieldProvider(
        Core08TimestampProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($resource->increment('order'))->toBe(1)
        ->and($resource->upsert(
            [['id' => 1, 'order' => 3]],
            ['id'],
            ['order'],
        ))->toBe(1)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('order'))->toBe(3)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('updated_at'))
        ->toBe($persistedTimestamp->format('Y-m-d H:i:s'));

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->getRawOriginal('updated_at'))
        ->toBe($persistedTimestamp->format('Y-m-d H:i:s'));
});

it('guards direct query writes reached through a model instance via :method', function (string $method) {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'direct write owner',
        'old_secret' => 'persisted secret',
        'old_count' => 10,
        'order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    Core08AttributeBoundaryProviderState::$teamId = 2;

    $write = match ($method) {
        'upsert' => fn (): mixed => $resource->upsert(
            [['id' => 1, 'old_secret' => 'upsert attack']],
            ['id'],
            ['old_secret'],
        ),
        'updateOrInsert' => fn (): mixed => $resource->updateOrInsert(
            ['id' => 1],
            ['old_secret' => 'update or insert attack'],
        ),
        'updateOrInsertClosure' => fn (): mixed => $resource->updateOrInsert(
            ['id' => 1],
            fn (): array => ['old_secret' => 'closure update or insert attack'],
        ),
        'insertOrIgnore' => fn (): mixed => $resource->insertOrIgnore([
            'id' => 2,
            'old_secret' => 'insert attack',
        ]),
        'nestedUpsert' => fn (): mixed => $resource->upsert(
            [['id' => 1, 'fields' => ['old_secret' => 'nested upsert attack']]],
            ['id'],
            ['fields'],
        ),
        'selectorUpdateOrInsert' => fn (): mixed => $resource->updateOrInsert(
            ['id' => 1],
            ['fields->profile->secret' => 'selector update or insert attack'],
        ),
    };

    expect($write)
        ->toThrow(LogicException::class, 'inactive provider-managed field')
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_secret'))->toBe('persisted secret')
        ->and(DB::table($resource->getTable())->count())->toBe(1);
})->with([
    'upsert' => 'upsert',
    'update or insert' => 'updateOrInsert',
    'update or insert closure' => 'updateOrInsertClosure',
    'insert or ignore' => 'insertOrIgnore',
    'nested upsert' => 'nestedUpsert',
    'selector update or insert' => 'selectorUpdateOrInsert',
]);

it('blocks explicit inactive touch columns without changing their pending state', function (string $method) {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'touch owner',
        'old_secret' => 'persisted secret',
        'old_count' => 10,
        'order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    $resource->old_secret = 'pending active secret';
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect(fn () => $resource->{$method}('old_secret'))
        ->toThrow(LogicException::class, 'inactive provider-managed field [old_secret]')
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_secret'))->toBe('persisted secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('pending active secret')
        ->and($resource->getRawOriginal('old_secret'))->toBe('persisted secret')
        ->and($resource->isDirty('old_secret'))->toBeTrue();
})->with([
    'touch' => 'touch',
    'touch quietly' => 'touchQuietly',
]);

it('keeps quiet save lifecycle paths isolated and permits nonmanaged instance upserts', function () {
    createCore08ProviderRecordsTable();
    DB::table((new Core08HydrationResource)->getTable())->insert([
        'id' => 1,
        'title' => 'quiet lifecycle owner',
        'old_secret' => 'persisted secret',
        'old_count' => 10,
        'order' => 1,
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);
    Aura::registerFieldProvider(
        Core08AttributeBoundaryProvider::class,
        resources: [Core08HydrationResource::class],
    );

    $resource = Core08HydrationResource::withoutGlobalScopes()->firstOrFail();
    $resource->old_secret = 'pending active secret';
    Core08AttributeBoundaryProviderState::$teamId = 2;

    expect($resource->updateQuietly(['order' => 2, 'old_secret' => 'quiet update attack']))->toBeTrue();

    $resource->order = 3;

    expect($resource->pushQuietly())->toBeTrue()
        ->and($resource->touch())->toBeTrue()
        ->and($resource->upsert(
            [['id' => 1, 'order' => 4]],
            ['id'],
            ['order'],
        ))->toBe(1)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('order'))->toBe(4)
        ->and(DB::table($resource->getTable())->where('id', 1)->value('old_secret'))->toBe('persisted secret');

    Core08AttributeBoundaryProviderState::$teamId = 1;

    expect($resource->old_secret)->toBe('pending active secret')
        ->and($resource->getRawOriginal('old_secret'))->toBe('persisted secret')
        ->and($resource->isDirty('old_secret'))->toBeTrue();
});

it('rejects provider object registrations', function () {
    expect(fn () => Aura::registerFieldProvider(
        new Core08VersionProvider,
        resources: [Core08AdversarialResource::class],
    ))->toThrow(InvalidArgumentException::class, 'class name');
});

it('rejects non-resource provider targets', function () {
    expect(fn () => Aura::registerFieldProvider(
        Core08VersionProvider::class,
        resources: [Text::class],
    ))->toThrow(InvalidArgumentException::class, 'Aura resource class names');
});

it('builds a fresh provider even when its class is bound as a singleton', function () {
    app()->singleton(Core08VersionProvider::class, fn (): Core08VersionProvider => new Core08VersionProvider);

    Aura::registerFieldProvider(
        Core08VersionProvider::class,
        resources: [Core08AdversarialResource::class],
    );
    Aura::captureBaselineState();

    expect((new Core08AdversarialResource)->fieldBySlug('versioned')['name'])->toBe('Version one-1');

    Aura::flushState();

    expect((new Core08AdversarialResource)->fieldBySlug('versioned')['name'])->toBe('Version one-1')
        ->and(Core08VersionProviderState::$instances)->toHaveCount(2)
        ->and(Core08VersionProviderState::$instances[0])->not->toBe(Core08VersionProviderState::$instances[1]);
});

it('keeps explicit user contexts isolated and memoized', function () {
    Aura::registerFieldProvider(
        Core08UserProvider::class,
        resources: [Core08AdversarialResource::class],
    );

    $resource = new Core08AdversarialResource;

    expect($resource->fieldBySlug('user_specific')['name'])->toBe('User 1')
        ->and($resource->fieldsCollection()->last()['name'])->toBe('User 1');

    Core08UserProviderState::$userId = 2;

    expect($resource->fieldBySlug('user_specific')['name'])->toBe('User 2')
        ->and($resource->fieldsCollection()->last()['name'])->toBe('User 2')
        ->and(Core08UserProviderState::$fieldsCalls)->toBe(2);
});

it('keeps untargeted resources declarative and memoized', function () {
    Aura::registerFieldProvider(
        Core08WildcardProvider::class,
        resources: [Core08AdversarialResource::class],
    );

    $resource = new Core08UnrelatedResource;

    expect($resource->fieldsCollection()->pluck('slug')->all())->toBe(['unrelated'])
        ->and($resource->fieldsCollection()->pluck('slug')->all())->toBe(['unrelated'])
        ->and(Core08UnrelatedResource::$declarationCalls)->toBe(1);
});

it('rejects invalid cache context values with controlled exceptions', function (array $context) {
    expect(fn () => (new FieldProviderContext(Core08AdversarialResource::class, $context))->fingerprint())
        ->toThrow(InvalidArgumentException::class, 'cache context');
})->with([
    'NAN' => fn (): array => ['value' => NAN],
    'positive infinity' => fn (): array => ['value' => INF],
    'negative infinity' => fn (): array => ['value' => -INF],
    'invalid UTF-8 value' => fn (): array => ['value' => "\xB1\x31"],
    'invalid UTF-8 key' => fn (): array => ["\xB1\x31" => 'value'],
    'numeric key' => fn (): array => [0 => 'value'],
    'nested array' => fn (): array => ['value' => ['nested']],
    'object' => fn (): array => ['value' => new stdClass],
    'closure' => fn (): array => ['value' => static fn (): bool => true],
    'resource' => fn (): array => ['value' => fopen('php://memory', 'r')],
]);

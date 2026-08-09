<?php

use Aura\Base\ConditionalLogic;
use Aura\Base\Contracts\FieldProvider;
use Aura\Base\Facades\Aura;
use Aura\Base\FieldProviderContext;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Traits\InputFields;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

class Core08ConditionalProvider implements FieldProvider
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

class Core08RefreshProvider implements FieldProvider
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
            'order' => 'integer',
        ];
    }
}

class Core08AttributeBoundaryProvider implements FieldProvider
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
        ];
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

class Core08UserProvider implements FieldProvider
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
}

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
        ->and($resource->hasAttribute('old_secret'))->toBeFalse()
        ->and($resource->getAttributes())->not->toHaveKey('old_secret');
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
        ->and($resource->old_secret)->toBeNull()
        ->and($resource->getAttribute('old_secret'))->toBeNull()
        ->and($resource->title)->toBe('Changed title')
        ->and($resource->parent)->toBe($parent)
        ->and(Core08AttributeBoundaryProviderState::$fieldsCalls)->toBe($flush ? 3 : 2);
})->with([
    'without flush' => false,
    'after flush' => true,
]);

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

    expect($resource->getFillable())->toContain('old_slug', 'cast_value')
        ->and($resource->getFillable())->not->toContain('new_slug')
        ->and($resource->fields->keys()->all())->toBe(['old_slug', 'cast_value'])
        ->and($resource->getMeta('cast_value'))->toBeFalse()
        ->and($resource->hasTableDisplayValue('old_slug'))->toBeTrue();

    Core08RefreshProviderState::$teamId = 2;
    Aura::flushFieldCache();

    expect($resource->getFillable())->toContain('new_slug', 'cast_value')
        ->and($resource->getFillable())->not->toContain('old_slug')
        ->and($resource->getAttributes())->not->toHaveKey('old_slug')
        ->and($resource->metaFields)->not->toHaveKey('old_slug')
        ->and($resource->fields->keys()->all())->toBe(['new_slug', 'cast_value'])
        ->and($resource->getMeta('cast_value'))->toBe('0')
        ->and($resource->hasTableDisplayValue('old_slug'))->toBeFalse();
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

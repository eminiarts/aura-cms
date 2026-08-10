<?php

namespace Tests\Feature\Fields;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\EmbeddedLivewireComponent;
use Aura\Base\Contracts\MapsEmbeddedComponentParameters;
use Aura\Base\Exceptions\InvalidEmbeddedComponentParameters;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\LivewireComponent;
use Aura\Base\Livewire\EmbeddedComponentAuthorizationHook;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Services\EmbeddedComponentContext;
use Aura\Base\Services\EmbeddedComponentResolver;
use Aura\Base\Services\EmbeddedComponentSurface;
use Aura\Base\Services\EmbeddedResourceIncarnationGuard;
use Aura\Base\Services\EmbeddedResourceIncarnationStore;
use Aura\Base\Services\MigrationOwnershipLedger;
use Aura\Base\Traits\AuthorizesEmbeddedComponent;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\ComponentHookRegistry;
use Livewire\Features\SupportEvents\SupportEvents;
use Livewire\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

use function Pest\Livewire\livewire;

class Core12EmbeddedComponent extends Component implements EmbeddedLivewireComponent
{
    use AuthorizesEmbeddedComponent;

    public static int $mountCount = 0;

    public static int $pingActionCount = 0;

    public static int $protectedListenerCount = 0;

    public static int $revokeActionCount = 0;

    public string $revokeOnUpdate = '';

    public static int $revokeUpdateCount = 0;

    public static int $sensitiveActionCount = 0;

    public string $sensitiveUpdate = '';

    public static int $sensitiveUpdateCount = 0;

    public function mount(): void
    {
        self::$mountCount++;
    }

    public function ping(): void
    {
        self::$pingActionCount++;
    }

    #[On('core12-protected-event')]
    public function protectedListener(): void
    {
        self::$protectedListenerCount++;
    }

    public function render(): string
    {
        $context = $this->embeddedContext();
        $resourceKey = $context->resource->getKey() ?? 'new';
        $nestedOwnerKey = $context->parameter('nested.owner.id') ?? 'none';

        return sprintf(
            '<div data-embedded-context="%s" data-embedded-field="%s">%s|%s|%s</div>',
            e($context->surface->value),
            e($context->fieldSlug),
            e((string) $context->parameter('label', '')),
            e((string) $resourceKey),
            e((string) $nestedOwnerKey),
        );
    }

    public function revokeAccess(): void
    {
        self::$revokeActionCount++;
        auth()->user()->forceFill(['global_admin' => false])->saveQuietly();
    }

    public function sensitiveAction(): void
    {
        self::$sensitiveActionCount++;
    }

    public function updatedRevokeOnUpdate(): void
    {
        self::$revokeUpdateCount++;
        auth()->user()->forceFill(['global_admin' => false])->saveQuietly();
    }

    public function updatedSensitiveUpdate(): void
    {
        self::$sensitiveUpdateCount++;
    }
}

class Core12FallbackEmbeddedComponent extends Core12EmbeddedComponent
{
    public function render(): string
    {
        return '<div data-embedded-fallback>Fallback component</div>';
    }
}

class Core12UnboundedComponent extends Component
{
    /** @var array<string, mixed> */
    public array $field = [];

    public mixed $model = null;

    public function render(): string
    {
        return <<<'HTML'
            <div data-legacy-embedded-field>
                Legacy|{{ $model?->getKey() ?? 'new' }}|{{ $field['slug'] ?? 'none' }}
            </div>
        HTML;
    }
}

class Core12MissingAuthorizationTraitComponent extends Component implements EmbeddedLivewireComponent
{
    public function render(): string
    {
        return '<div>Missing authorization trait</div>';
    }
}

class Core12ZeroArgumentIndexField extends Field
{
    public function rendersOnIndex()
    {
        return true;
    }
}

class Core12ZeroArgumentLivewireIndexField extends LivewireComponent
{
    public function rendersOnIndex()
    {
        return true;
    }
}

class Core12ParameterMapper implements MapsEmbeddedComponentParameters
{
    public static int $mapCount = 0;

    public function map(EmbeddedComponentContext $context): array
    {
        self::$mapCount++;

        return [
            'label' => 'Mapped '.$context->surface->value,
            'nested' => [
                'owner' => [
                    'id' => $context->resource?->getKey(),
                ],
            ],
        ];
    }
}

class Core12UnsafeParameterMapper implements MapsEmbeddedComponentParameters
{
    public function map(EmbeddedComponentContext $context): array
    {
        return [
            'modelGraph' => $context->resource,
            'object' => new stdClass,
        ];
    }
}

class Core12BoundedParameterMapper implements MapsEmbeddedComponentParameters
{
    /** @var array<string, mixed> */
    public static array $output = [];

    public function map(EmbeddedComponentContext $context): array
    {
        return self::$output;
    }
}

class Core12EmbeddedResource extends Resource
{
    public static ?string $slug = 'core12-embedded-resource';

    public static string $type = 'Core12EmbeddedResource';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Embedded surface',
                'slug' => 'embedded_surface',
                'type' => LivewireComponent::class,
                'component_aliases' => [
                    'edit' => 'core12.embedded.edit',
                    'view' => 'core12.embedded.view',
                    'index' => 'core12.embedded.index',
                ],
                'parameter_mapper' => Core12ParameterMapper::class,
                'on_forms' => true,
                'on_view' => true,
                'on_index' => true,
            ],
        ];
    }
}

class Core12ScopedOccupiedResource extends Core12EmbeddedResource
{
    public static ?string $slug = 'core12-scoped-occupied-resource';

    public static string $type = 'Core12ScopedOccupied';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('core12-visible-status', function (Builder $builder): void {
            $builder->where('posts.status', 'core12-visible');
        });
    }
}

class Core12UuidEmbeddedResource extends Core12EmbeddedResource
{
    public static $customTable = true;

    public $incrementing = false;

    public static ?string $slug = 'core12-uuid-embedded-resource';

    public static string $type = 'Core12UuidEmbeddedResource';

    public static bool $usesMeta = false;

    protected $fillable = ['id', 'title', 'user_id', 'team_id'];

    protected $keyType = 'string';

    protected $table = 'core12_uuid_embedded_resources';
}

class Core12TeamBoundEmbeddedResource extends Core12UuidEmbeddedResource
{
    public static ?string $slug = 'core12-team-bound-embedded-resource';

    public static string $type = 'Core12TeamBoundEmbeddedResource';
}

class Core12TeamBoundCreatePolicy
{
    public function create(User $user, Core12TeamBoundEmbeddedResource $resource): bool
    {
        return $resource->getAttribute('team_id') === null
            || $resource->getAttribute('team_id') === $user->getAttribute('current_team_id');
    }
}

class Core12OwnerBoundEmbeddedResource extends Core12UuidEmbeddedResource
{
    public static ?string $slug = 'core12-owner-bound-embedded-resource';

    public static string $type = 'Core12OwnerBoundEmbeddedResource';

    public function embeddedAuthorizationAttributeNames(): array
    {
        return ['account_owner_id'];
    }
}

class Core12OwnerBoundCreatePolicy
{
    public function create(User $user, Core12OwnerBoundEmbeddedResource $resource): bool
    {
        return $resource->getAttribute('account_owner_id') === $user->getKey();
    }
}

class Core12UncontractedEmbeddedResource extends Model implements DefinesFields
{
    public static function getFields(): array
    {
        return [];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createGlobalAdmin());

    Livewire::component('core12.embedded.edit', Core12EmbeddedComponent::class);
    Livewire::component('core12.embedded.view', Core12EmbeddedComponent::class);
    Livewire::component('core12.embedded.index', Core12EmbeddedComponent::class);
    Livewire::component('core12.embedded.fallback', Core12FallbackEmbeddedComponent::class);
    Livewire::component('core12.missing-authorization-trait', Core12MissingAuthorizationTraitComponent::class);
    Livewire::component('core12.unbounded', Core12UnboundedComponent::class);
    app(EmbeddedResourceIncarnationGuard::class)->install(Core12EmbeddedResource::class);

    Core12EmbeddedComponent::$mountCount = 0;
    Core12EmbeddedComponent::$pingActionCount = 0;
    Core12EmbeddedComponent::$protectedListenerCount = 0;
    Core12EmbeddedComponent::$revokeActionCount = 0;
    Core12EmbeddedComponent::$revokeUpdateCount = 0;
    Core12EmbeddedComponent::$sensitiveActionCount = 0;
    Core12EmbeddedComponent::$sensitiveUpdateCount = 0;
    Core12ParameterMapper::$mapCount = 0;
    Core12BoundedParameterMapper::$output = [];
});

function core12Field(array $overrides = []): array
{
    return array_replace(Core12EmbeddedResource::getFields()[0], $overrides);
}

function createCore12UuidEmbeddedResourcesTable(): void
{
    Schema::create('core12_uuid_embedded_resources', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('title')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    app(EmbeddedResourceIncarnationGuard::class)->install(Core12UuidEmbeddedResource::class);
}

describe('LivewireComponent field configuration', function () {
    test('keeps the public rendersOnIndex extension point zero argument compatible', function () {
        expect((new ReflectionMethod(Field::class, 'rendersOnIndex'))->getNumberOfParameters())
            ->toBe(0)
            ->and((new Core12ZeroArgumentIndexField)->rendersConfiguredFieldOnIndex([]))
            ->toBeTrue();
    });

    test('keeps untyped rendersOnIndex overrides compatible on the Livewire field itself', function (): void {
        expect((new ReflectionMethod(LivewireComponent::class, 'rendersOnIndex'))->hasReturnType())
            ->toBeFalse()
            ->and((new Core12ZeroArgumentLivewireIndexField)->rendersOnIndex())
            ->toBeTrue();
    });

    test('registers embedded authorization before Livewire executable hooks', function () {
        $reflection = new ReflectionClass(ComponentHookRegistry::class);
        $hooks = $reflection->getStaticPropertyValue('componentHooks');

        expect(array_search(EmbeddedComponentAuthorizationHook::class, $hooks, true))
            ->toBeLessThan(array_search(SupportEvents::class, $hooks, true))
            ->and(array_search(EmbeddedComponentAuthorizationHook::class, $hooks, true))
            ->toBeLessThan(array_search(SupportLifecycleHooks::class, $hooks, true));
    });

    test('declares explicit edit view and index renderers without becoming an input', function () {
        $field = new LivewireComponent;

        expect($field->edit)->toBe('aura::fields.livewire-component')
            ->and($field->view)->toBe('aura::fields.livewire-component-view')
            ->and($field->index)->toBe('aura::fields.livewire-component-index')
            ->and($field->type)->toBe('livewire-component')
            ->and($field->isInputField())->toBeFalse()
            ->and($field->isRelation())->toBeFalse();
    });

    test('exposes aliases mapper fallback and owner resource configuration', function () {
        $fields = collect((new LivewireComponent)->getFields());

        expect($fields->firstWhere('slug', 'component_aliases.edit'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'component_aliases.view'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'component_aliases.index'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'component_aliases.fallback'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'parameter_mapper'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'owner_resource'))->not->toBeNull();
    });
});

describe('embedded component resolution', function () {
    test('resolves the explicit alias and custom serializable parameters for every surface', function (EmbeddedComponentSurface $surface, string $alias) {
        $resource = Core12EmbeddedResource::create(['title' => 'Embedded owner']);

        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: $surface,
        );

        expect($definition)->not->toBeNull()
            ->and($definition->alias)->toBe($alias)
            ->and($definition->parameters)->toHaveKeys(['auraEmbeddedContext'])
            ->and($definition->parameters)->not->toHaveKeys([
                'model',
                'field',
                'resourceType',
                'resourceId',
                'fieldSlug',
                'context',
                'label',
                'nested',
            ])
            ->and($definition->parameters['auraEmbeddedContext'])->toMatchArray([
                'surface' => $surface->value,
                'field_slug' => 'embedded_surface',
                'parameters' => [
                    'label' => 'Mapped '.$surface->value,
                    'nested' => ['owner' => ['id' => $resource->getKey()]],
                ],
            ]);
    })->with([
        'edit' => [EmbeddedComponentSurface::Edit, 'core12.embedded.edit'],
        'view' => [EmbeddedComponentSurface::View, 'core12.embedded.view'],
        'index' => [EmbeddedComponentSurface::Index, 'core12.embedded.index'],
    ]);

    test('keeps create parameters model-free with a null resource key', function () {
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['owner_resource' => Core12EmbeddedResource::class]),
            resource: null,
            surface: EmbeddedComponentSurface::Edit,
        );

        expect($definition)->not->toBeNull()
            ->and($definition->parameters)->toHaveKeys(['auraEmbeddedContext'])
            ->and($definition->parameters)->not->toHaveKeys([
                'model',
                'field',
                'resourceId',
                'resourceType',
            ])
            ->and($definition->parameters['auraEmbeddedContext'])->toMatchArray([
                'resource_key' => null,
                'persisted' => false,
                'resource_authorization_attributes' => [],
            ]);

        Livewire::test($definition->alias, $definition->parameters)
            ->assertSee('Mapped edit|new|none');
    });

    test('uses a declared bounded fallback when the surface alias is not configured', function (array $aliases) {
        $resource = Core12EmbeddedResource::create(['title' => 'Fallback owner']);

        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['component_aliases' => $aliases]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );

        expect($definition)->not->toBeNull()
            ->and($definition->alias)->toBe('core12.embedded.fallback');
    })->with([
        'unsupported context' => [[
            'fallback' => 'core12.embedded.fallback',
        ]],
    ]);

    test('fails closed for a missing alias or a component outside the embedded boundary', function (array $aliases) {
        $resource = Core12EmbeddedResource::create(['title' => 'Closed owner']);

        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['component_aliases' => $aliases]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );

        expect($definition)->toBeNull();
    })->with([
        'missing' => [['view' => 'core12.does-not-exist']],
        'invalid alias' => [['view' => '<script>']],
        'missing authorization trait' => [['view' => 'core12.missing-authorization-trait']],
        'unbounded' => [['view' => 'core12.unbounded']],
    ]);

    test('fails closed instead of falling back when an explicit secure alias is malformed', function (array $overrides, EmbeddedComponentSurface $surface) {
        $resource = Core12EmbeddedResource::create(['title' => 'Malformed secure configuration']);

        expect(app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field($overrides),
            resource: $resource,
            surface: $surface,
        ))->toBeNull();
    })->with([
        'invalid explicit view alias does not use secure fallback' => [[
            'component_aliases' => [
                'view' => 'core12.does-not-exist',
                'fallback' => 'core12.embedded.fallback',
            ],
        ], EmbeddedComponentSurface::View],
        'invalid explicit edit configuration does not use legacy component' => [[
            'component' => 'core12.unbounded',
            'component_aliases' => ['edit' => ['core12.embedded.edit']],
        ], EmbeddedComponentSurface::Edit],
        'invalid aliases container does not use legacy component' => [[
            'component' => 'core12.unbounded',
            'component_aliases' => 'core12.embedded.edit',
        ], EmbeddedComponentSurface::Edit],
    ]);

    test('preserves the legacy edit-only component contract without opting it into secure surfaces', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Legacy owner']);
        $field = [
            'name' => 'Legacy surface',
            'slug' => 'legacy_surface',
            'type' => LivewireComponent::class,
            'component' => 'core12.unbounded',
        ];

        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: $field,
            resource: $resource,
            surface: EmbeddedComponentSurface::Edit,
        );

        expect($definition?->alias)->toBe('core12.unbounded')
            ->and($definition?->parameters['model'])->toBe($resource)
            ->and($definition?->parameters['field'])->toBe($field)
            ->and(app(EmbeddedComponentResolver::class)->resolve(
                field: $field,
                resource: $resource,
                surface: EmbeddedComponentSurface::View,
            ))->toBeNull()
            ->and((new LivewireComponent)->rendersConfiguredFieldOnIndex($field))->toBeFalse();

        Livewire::test($definition->alias, $definition->parameters)
            ->assertSee('Legacy|'.$resource->getKey().'|legacy_surface');
    });

    test('keeps the Aura user two-factor field on its legacy edit contract and out of index columns', function () {
        $field = collect(User::getFields())->firstWhere('slug', '2fa');
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: $field,
            resource: $this->user,
            surface: EmbeddedComponentSurface::Edit,
        );

        expect($definition?->alias)->toBe('aura::two-factor-authentication-form')
            ->and($definition?->parameters['model'])->toBe($this->user)
            ->and((new User)->indexFields()->pluck('slug'))->not->toContain('2fa');
    });
});

describe('embedded component security and identity', function () {
    test('does not resolve an owning resource the actor cannot view', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Denied owner']);
        $this->actingAs(User::factory()->create());

        expect(app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        ))->toBeNull();
    });

    test('rejects mapper output containing model graphs or objects', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Unsafe owner']);

        expect(fn () => app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['parameter_mapper' => Core12UnsafeParameterMapper::class]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        ))->toThrow(InvalidEmbeddedComponentParameters::class, 'scalar and array values');
    });

    test('rejects mapper output whose encoded payload exceeds the aggregate byte limit', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Oversized mapper bytes']);
        Core12BoundedParameterMapper::$output = ['payload' => str_repeat('x', 2 * 1024 * 1024)];

        expect(fn () => app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['parameter_mapper' => Core12BoundedParameterMapper::class]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        ))->toThrow(InvalidEmbeddedComponentParameters::class, 'string byte limit');
    });

    test('rejects mapper output whose aggregate element count is unbounded', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Oversized mapper elements']);
        Core12BoundedParameterMapper::$output = ['payload' => array_fill(0, 100_000, 'x')];

        expect(fn () => app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['parameter_mapper' => Core12BoundedParameterMapper::class]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        ))->toThrow(InvalidEmbeddedComponentParameters::class, 'element limit');
    });

    test('rejects mapper output whose aggregate encoding exceeds the context byte limit', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Aggregate mapper bytes']);
        Core12BoundedParameterMapper::$output = array_fill(0, 16, str_repeat('x', 8192));

        expect(fn () => app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['parameter_mapper' => Core12BoundedParameterMapper::class]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        ))->toThrow(InvalidEmbeddedComponentParameters::class, 'encoded byte limit');
    });

    test('rejects mapper output with oversized keys or excessive nesting', function (array $output, string $message) {
        $resource = Core12EmbeddedResource::create(['title' => 'Structurally unsafe mapper']);
        Core12BoundedParameterMapper::$output = $output;

        expect(fn () => app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['parameter_mapper' => Core12BoundedParameterMapper::class]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        ))->toThrow(InvalidEmbeddedComponentParameters::class, $message);
    })->with([
        'oversized key' => [[str_repeat('k', 192) => 'value'], 'key byte limit'],
        'excessive nesting' => [[
            'one' => ['two' => ['three' => ['four' => ['five' => ['six' => ['seven' => ['eight' => ['nine' => ['ten' => ['eleven' => 'value']]]]]]]]]],
        ], 'nesting depth'],
    ]);

    test('reauthorizes the owning resource on every embedded request', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Changing permissions']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        $this->actingAs(User::factory()->create());

        $component->call('ping')->assertForbidden();
    });

    test('rejects tampered owner metadata and cross-component context reuse', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Signed owner']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );

        $tamperedParameters = $definition->parameters;
        $tamperedParameters['auraEmbeddedContext']['resource_key'] = $resource->getKey() + 1;
        Core12EmbeddedComponent::$mountCount = 0;

        Livewire::test($definition->alias, $tamperedParameters)
            ->assertForbidden();

        expect(Core12EmbeddedComponent::$mountCount)->toBe(0);

        Livewire::test('core12.embedded.edit', $definition->parameters)
            ->assertForbidden();
    });

    test('rejects tampered mapper values inside the signed authoritative context', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Signed mapper values']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $tamperedParameters = $definition->parameters;
        $tamperedParameters['auraEmbeddedContext']['parameters']['label'] = 'Forged';

        Livewire::test($definition->alias, $tamperedParameters)
            ->assertForbidden();
    });

    test('expires signed embedded contexts', function () {
        config(['aura.embedded_components.context_ttl' => 60]);
        $resource = Core12EmbeddedResource::create(['title' => 'Expiring context']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        $this->travel(61)->seconds();
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();

        $this->travelBack();
    });

    test('invalidates signed embedded contexts when the configured revision changes', function () {
        config(['aura.embedded_components.context_revision' => 'core12-v1']);
        $resource = Core12EmbeddedResource::create(['title' => 'Revisioned context']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        config(['aura.embedded_components.context_revision' => 'core12-v2']);
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
    });

    test('rejects public property updates carrying a stale signed context', function (): void {
        config(['aura.embedded_components.context_revision' => 'core12-update-v1']);
        $resource = Core12EmbeddedResource::create(['title' => 'Stale update context']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        config(['aura.embedded_components.context_revision' => 'core12-update-v2']);
        app()->forgetScopedInstances();

        $component->set('sensitiveUpdate', 'stale')->assertForbidden();

        expect(Core12EmbeddedComponent::$sensitiveUpdateCount)->toBe(0);
    });

    test('authorizes public property update hooks', function (): void {
        $resource = Core12EmbeddedResource::create(['title' => 'Authorized property update']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );

        Livewire::test($definition->alias, $definition->parameters)
            ->update(updates: ['sensitiveUpdate' => 'allowed'])
            ->assertOk()
            ->assertSet('sensitiveUpdate', 'allowed');

        expect(Core12EmbeddedComponent::$sensitiveUpdateCount)->toBe(1);
    });

    test('rejects unauthorized public property updates before their hooks execute', function (): void {
        $resource = Core12EmbeddedResource::create(['title' => 'Unauthorized property update']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        $this->actingAs(User::factory()->create());

        $component->set('sensitiveUpdate', 'blocked')->assertForbidden();

        expect(Core12EmbeddedComponent::$sensitiveUpdateCount)->toBe(0);
    });

    test('reauthorizes before each public property update in one batched request', function (): void {
        $resource = Core12EmbeddedResource::create(['title' => 'Batched property authorization']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        $component->update(updates: [
            'revokeOnUpdate' => 'revoke',
            'sensitiveUpdate' => 'blocked',
        ])->assertForbidden();

        expect(Core12EmbeddedComponent::$revokeUpdateCount)->toBe(1)
            ->and(Core12EmbeddedComponent::$sensitiveUpdateCount)->toBe(0)
            ->and($this->user->fresh()->global_admin)->toBeFalse();
    });

    test('reauthorizes before each action in one batched Livewire request', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Batched authorization']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        $component->update(calls: [
            ['method' => 'revokeAccess', 'params' => [], 'path' => ''],
            ['method' => 'sensitiveAction', 'params' => [], 'path' => ''],
        ])->assertForbidden();

        expect(Core12EmbeddedComponent::$revokeActionCount)->toBe(1)
            ->and(Core12EmbeddedComponent::$sensitiveActionCount)->toBe(0)
            ->and($this->user->fresh()->global_admin)->toBeFalse();
    });

    test('authorizes before a dispatched listener can run in a batched Livewire request', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Protected event listener']);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        $component->update(calls: [
            ['method' => 'revokeAccess', 'params' => [], 'path' => ''],
            ['method' => '__dispatch', 'params' => ['core12-protected-event', []], 'path' => ''],
        ])->assertForbidden();

        expect(Core12EmbeddedComponent::$revokeActionCount)->toBe(1)
            ->and(Core12EmbeddedComponent::$protectedListenerCount)->toBe(0)
            ->and($this->user->fresh()->global_admin)->toBeFalse();
    });

    test('reauthorizes the same bounded unsaved owner after a team context change', function () {
        if (! Schema::hasColumn('users', 'current_team_id')) {
            $this->markTestSkipped('Team-bound create context requires the teams schema.');
        }

        Gate::policy(Core12TeamBoundEmbeddedResource::class, Core12TeamBoundCreatePolicy::class);

        $user = User::factory()->create(['current_team_id' => 10]);
        $this->actingAs($user);
        $resource = new Core12TeamBoundEmbeddedResource(['team_id' => 10]);
        $resource->setAttribute('private_create_draft', 'must-not-be-serialized');
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::Edit,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        expect($definition->parameters['auraEmbeddedContext']['resource_authorization_attributes'])
            ->toBe(['team_id' => 10])
            ->not->toHaveKey('private_create_draft');

        $user->forceFill(['current_team_id' => 20])->saveQuietly();
        $this->actingAs($user->refresh());
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
    });

    test('uses an explicit bounded owner attribute contract for create authorization', function () {
        Gate::policy(Core12OwnerBoundEmbeddedResource::class, Core12OwnerBoundCreatePolicy::class);

        $resource = new Core12OwnerBoundEmbeddedResource;
        $resource->setAttribute('account_owner_id', $this->user->getKey());
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::Edit,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        expect($definition->parameters['auraEmbeddedContext']['resource_authorization_attributes'])
            ->toBe(['account_owner_id' => $this->user->getKey()]);

        $this->actingAs(createGlobalAdmin());
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
    });

    test('fails closed for attributed unsaved owners without an authorization attribute contract', function () {
        Gate::define('create', fn (User $user, Core12UncontractedEmbeddedResource $resource): bool => true);
        $resource = new Core12UncontractedEmbeddedResource;
        $resource->setAttribute('team_id', 10);

        expect(app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::Edit,
        ))->toBeNull();
    });

    test('hydrates an unsaved resource with a preassigned UUID as create context', function () {
        createCore12UuidEmbeddedResourcesTable();

        $resource = new Core12UuidEmbeddedResource;
        $resource->setAttribute($resource->getKeyName(), '00000000-0000-4000-8000-000000000012');
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::Edit,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        app()->forgetScopedInstances();

        $component->call('ping')->assertOk();
    });

    test('rejects a preassigned create key occupied outside every Eloquent scope', function () {
        if (! Schema::hasColumn('users', 'current_team_id')) {
            $this->markTestSkipped('Cross-tenant occupancy requires the teams schema.');
        }

        $foreignTeam = foreignTeam();
        $occupiedKey = DB::table('posts')->insertGetId([
            'title' => 'Physically occupied elsewhere',
            'type' => 'OtherResource',
            'status' => 'hidden',
            'team_id' => $foreignTeam->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $resource = new Core12ScopedOccupiedResource([
            'team_id' => $this->user->getAttribute('current_team_id'),
        ]);
        $resource->setAttribute($resource->getKeyName(), $occupiedKey);

        expect(Core12ScopedOccupiedResource::query()->whereKey($occupiedKey)->exists())->toBeFalse()
            ->and(app(EmbeddedComponentResolver::class)->resolve(
                field: core12Field(),
                resource: $resource,
                surface: EmbeddedComponentSurface::Edit,
            ))->toBeNull();
    });

    test('hydrates saved UUID resources and rejects delete-recreate identity reuse', function () {
        createCore12UuidEmbeddedResourcesTable();

        $key = '00000000-0000-4000-8000-000000000013';
        $resource = Core12UuidEmbeddedResource::create([
            'id' => $key,
            'title' => 'Original row',
        ]);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        app()->forgetScopedInstances();
        $component->call('ping')->assertOk();

        $originalAttributes = $resource->getRawOriginal();
        $resource->delete();
        DB::table('core12_uuid_embedded_resources')->insert($originalAttributes);
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
    });

    test('fails closed without installing schema during a persisted context request', function () {
        Schema::create('core12_uuid_embedded_resources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
        $resource = Core12UuidEmbeddedResource::create([
            'id' => '00000000-0000-4000-8000-000000000016',
            'title' => 'Missing guard',
        ]);
        $triggersBefore = DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->where('tbl_name', $resource->getTable())
            ->count();

        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );

        expect($definition)->toBeNull()
            ->and(DB::table('sqlite_master')
                ->where('type', 'trigger')
                ->where('tbl_name', $resource->getTable())
                ->count())->toBe($triggersBefore);
    });

    test('fails closed while preloading an index whose persisted owner has no guard', function () {
        Schema::create('core12_uuid_embedded_resources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id')->nullable();
            $table->timestamps();
        });
        $resource = Core12UuidEmbeddedResource::create([
            'id' => '00000000-0000-4000-8000-000000000024',
            'title' => 'Missing index guard',
        ]);

        (new LivewireComponent)->preloadTableDisplay(
            new EloquentCollection([$resource]),
            core12Field(),
        );

        expect(DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->where('tbl_name', $resource->getTable())
            ->count())->toBe(0);
    });

    test('fails closed when a deployed guard disappears before a component action', function () {
        createCore12UuidEmbeddedResourcesTable();
        $resource = Core12UuidEmbeddedResource::create([
            'id' => '00000000-0000-4000-8000-000000000022',
            'title' => 'Removed guard',
        ]);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        app(EmbeddedResourceIncarnationGuard::class)->uninstall($resource);
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
        expect(DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->where('tbl_name', $resource->getTable())
            ->count())->toBe(0);
    });

    test('rejects delete-recreate identity reuse when deletion bypasses Eloquent events', function (Closure $delete): void {
        createCore12UuidEmbeddedResourcesTable();

        $key = '00000000-0000-4000-8000-000000000015';
        $resource = Core12UuidEmbeddedResource::create([
            'id' => $key,
            'title' => 'Bypassed lifecycle events',
        ]);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();
        $originalAttributes = $resource->getRawOriginal();

        $delete($resource);
        DB::table($resource->getTable())->insert($originalAttributes);
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
    })->with([
        'quiet model delete' => fn (Core12UuidEmbeddedResource $resource): bool => $resource->deleteQuietly(),
        'Eloquent bulk delete' => fn (Core12UuidEmbeddedResource $resource): int => $resource->newQuery()->whereKey($resource->getKey())->delete(),
        'raw query delete' => fn (Core12UuidEmbeddedResource $resource): int => DB::table($resource->getTable())->where($resource->getKeyName(), $resource->getKey())->delete(),
    ]);

    test('rejects the original key after a raw primary-key update and reinsert', function (): void {
        createCore12UuidEmbeddedResourcesTable();

        $originalKey = '00000000-0000-4000-8000-000000000017';
        $replacementKey = '00000000-0000-4000-8000-000000000018';
        $resource = Core12UuidEmbeddedResource::create([
            'id' => $originalKey,
            'title' => 'Original key',
        ]);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();
        $originalAttributes = $resource->getRawOriginal();

        DB::table($resource->getTable())
            ->where($resource->getKeyName(), $originalKey)
            ->update([$resource->getKeyName() => $replacementKey]);
        DB::table($resource->getTable())->insert($originalAttributes);
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
    });

    test('rejects every old context after one raw bulk delete and byte-identical reinsert', function (): void {
        createCore12UuidEmbeddedResourcesTable();

        $resources = collect([
            '00000000-0000-4000-8000-000000000019',
            '00000000-0000-4000-8000-000000000020',
        ])->map(fn (string $key): Core12UuidEmbeddedResource => Core12UuidEmbeddedResource::create([
            'id' => $key,
            'title' => 'Bulk '.$key,
        ]));
        $components = $resources->map(function (Core12UuidEmbeddedResource $resource) {
            $definition = app(EmbeddedComponentResolver::class)->resolve(
                field: core12Field(),
                resource: $resource,
                surface: EmbeddedComponentSurface::View,
            );

            return Livewire::test($definition->alias, $definition->parameters)->assertOk();
        });
        $attributes = $resources->map->getRawOriginal()->all();

        DB::table((new Core12UuidEmbeddedResource)->getTable())
            ->whereIn('id', $resources->map->getKey()->all())
            ->delete();
        DB::table((new Core12UuidEmbeddedResource)->getTable())->insert($attributes);
        app()->forgetScopedInstances();

        $components->each(
            fn ($component) => $component->call('ping')->assertForbidden(),
        );
    });

    test('does not swallow an incarnation store query failure', function (): void {
        createCore12UuidEmbeddedResourcesTable();
        $resource = Core12UuidEmbeddedResource::create([
            'id' => '00000000-0000-4000-8000-000000000021',
            'title' => 'Store failure',
        ]);
        app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );

        Schema::drop(EmbeddedResourceIncarnationStore::TABLE);

        try {
            expect(fn () => app(EmbeddedResourceIncarnationStore::class)->rotate($resource))
                ->toThrow(QueryException::class);
        } finally {
            DB::table(MigrationOwnershipLedger::TABLE)
                ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
                ->delete();
            $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
            $migration->up();
        }
    });

    test('does not swallow an incarnation read failure during component authorization', function (): void {
        createCore12UuidEmbeddedResourcesTable();
        $resource = Core12UuidEmbeddedResource::create([
            'id' => '00000000-0000-4000-8000-000000000023',
            'title' => 'Read failure',
        ]);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        Schema::drop(EmbeddedResourceIncarnationStore::TABLE);

        try {
            expect(fn () => $component->call('ping'))->toThrow(QueryException::class);
        } finally {
            DB::table(MigrationOwnershipLedger::TABLE)
                ->where('migration', MigrationOwnershipLedger::CREATE_KEY)
                ->delete();
            $migration = require dirname(__DIR__, 3).'/database/migrations/create_embedded_resource_incarnations.php.stub';
            $migration->up();
        }
    });

    test('signs the canonical fully-loaded row instead of a partial model projection', function () {
        createCore12UuidEmbeddedResourcesTable();

        $key = '00000000-0000-4000-8000-000000000014';
        Core12UuidEmbeddedResource::create([
            'id' => $key,
            'title' => 'Canonical title',
        ]);
        $partialResource = Core12UuidEmbeddedResource::query()
            ->select('id')
            ->findOrFail($key);
        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(),
            resource: $partialResource,
            surface: EmbeddedComponentSurface::View,
        );
        $component = Livewire::test($definition->alias, $definition->parameters)
            ->assertOk();

        DB::table('core12_uuid_embedded_resources')
            ->where('id', $key)
            ->update(['title' => 'Changed outside the projection']);
        app()->forgetScopedInstances();

        $component->call('ping')->assertForbidden();
    });

    test('keeps nested identities stable and unique by field and owner', function () {
        $first = Core12EmbeddedResource::create(['title' => 'First owner']);
        $second = Core12EmbeddedResource::create(['title' => 'Second owner']);
        $resolver = app(EmbeddedComponentResolver::class);

        $firstDefinition = $resolver->resolve(
            field: core12Field(['_id' => 4, '_parent_id' => 2]),
            resource: $first,
            surface: EmbeddedComponentSurface::Index,
        );
        $sameDefinition = $resolver->resolve(
            field: core12Field(['_id' => 4, '_parent_id' => 2]),
            resource: $first,
            surface: EmbeddedComponentSurface::Index,
        );
        $nestedDefinition = $resolver->resolve(
            field: core12Field(['_id' => 5, '_parent_id' => 3]),
            resource: $first,
            surface: EmbeddedComponentSurface::Index,
        );
        $secondDefinition = $resolver->resolve(
            field: core12Field(['_id' => 4, '_parent_id' => 2]),
            resource: $second,
            surface: EmbeddedComponentSurface::Index,
        );

        expect($firstDefinition->key)->toBe($sameDefinition->key)
            ->and($firstDefinition->key)->not->toBe($nestedDefinition->key)
            ->and($firstDefinition->key)->not->toBe($secondDefinition->key);
    });
});

describe('resource surfaces', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new Core12EmbeddedResource);
    });

    test('renders the configured component on create edit view and index', function () {
        $resource = Core12EmbeddedResource::create(['title' => 'Surface owner']);

        $this->get(route('aura.core12-embedded-resource.create'))
            ->assertOk()
            ->assertSee('Mapped edit|new|none');

        $this->get(route('aura.core12-embedded-resource.edit', $resource))
            ->assertOk()
            ->assertSee('Mapped edit|'.$resource->getKey().'|'.$resource->getKey());

        $this->get(route('aura.core12-embedded-resource.view', $resource))
            ->assertOk()
            ->assertSee('Mapped view|'.$resource->getKey().'|'.$resource->getKey());

        $this->get(route('aura.core12-embedded-resource.index'))
            ->assertOk()
            ->assertSee('Mapped index|'.$resource->getKey().'|'.$resource->getKey());
    });

    test('does not persist the embedded field as resource data', function () {
        expect((new Core12EmbeddedResource)->inputFieldsSlugs())
            ->not->toContain('embedded_surface');
    });

    test('mounts real index children without per-row owner lookups', function () {
        foreach (range(1, 10) as $index) {
            Core12EmbeddedResource::create(['title' => 'Owner '.$index]);
        }

        $authorizationChecks = 0;
        Gate::after(function (mixed $user, string $ability, mixed $result, array $arguments) use (&$authorizationChecks): void {
            if (($arguments[0] ?? null) instanceof Core12EmbeddedResource) {
                $authorizationChecks++;
            }
        });

        DB::enableQueryLog();
        DB::flushQueryLog();

        livewire(Table::class, [
            'query' => null,
            'model' => new Core12EmbeddedResource,
        ])->assertSee('Mapped index');

        $ownerSelects = collect(DB::getQueryLog())->filter(function (array $query): bool {
            $sql = strtolower($query['query']);

            return str_contains($sql, 'from "posts"') || str_contains($sql, 'from `posts`');
        });
        $incarnationSelects = collect(DB::getQueryLog())->filter(function (array $query): bool {
            return str_starts_with(strtolower($query['query']), 'select')
                && str_contains($query['query'], 'aura_embedded_resource_incarnations');
        });

        DB::disableQueryLog();

        expect($ownerSelects)->toHaveCount(3)
            ->and($incarnationSelects)->toHaveCount(1)
            ->and(Core12ParameterMapper::$mapCount)->toBe(10)
            ->and(Core12EmbeddedComponent::$mountCount)->toBe(10)
            ->and($authorizationChecks)->toBeGreaterThanOrEqual(20);
    });

    test('reauthorizes a bundled second request without per-child owner queries or cross-user cache bleed', function () {
        $resources = collect(range(1, 10))->map(
            fn (int $index) => Core12EmbeddedResource::create(['title' => 'Bundled owner '.$index]),
        );
        $components = $resources->map(function (Core12EmbeddedResource $resource) {
            $definition = app(EmbeddedComponentResolver::class)->resolve(
                field: core12Field(),
                resource: $resource,
                surface: EmbeddedComponentSurface::Index,
            );

            return Livewire::test($definition->alias, $definition->parameters)->assertOk();
        });
        $payload = $components->map(fn ($component): array => [
            'snapshot' => json_encode($component->snapshot, JSON_THROW_ON_ERROR),
            'updates' => [],
            'calls' => [['method' => 'ping', 'params' => [], 'path' => '']],
        ])->all();
        $authorizationChecks = 0;
        Gate::after(function (mixed $user, string $ability, mixed $result, array $arguments) use (&$authorizationChecks): void {
            if (($arguments[0] ?? null) instanceof Core12EmbeddedResource) {
                $authorizationChecks++;
            }
        });

        app()->forgetScopedInstances();
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->withHeader('X-Livewire', 'true')
            ->postJson(app('livewire')->getUpdateUri(), ['components' => $payload])
            ->assertOk()
            ->assertJsonCount(10, 'components');

        $queries = collect(DB::getQueryLog());
        $ownerSelects = $queries->filter(function (array $query): bool {
            $sql = strtolower($query['query']);

            return str_starts_with($sql, 'select')
                && (str_contains($sql, 'from "posts"') || str_contains($sql, 'from `posts`'));
        });
        $incarnationSelects = $queries->filter(function (array $query): bool {
            return str_starts_with(strtolower($query['query']), 'select')
                && str_contains($query['query'], 'aura_embedded_resource_incarnations');
        });

        DB::disableQueryLog();

        expect($ownerSelects)->toHaveCount(1)
            ->and($incarnationSelects)->toHaveCount(1)
            ->and($authorizationChecks)->toBe(20)
            ->and(Core12EmbeddedComponent::$pingActionCount)->toBe(10);

        app()->forgetScopedInstances();
        $this->actingAs(User::factory()->create());

        $this->withHeader('X-Livewire', 'true')
            ->postJson(app('livewire')->getUpdateUri(), ['components' => $payload])
            ->assertForbidden();

        expect(Core12EmbeddedComponent::$pingActionCount)->toBe(10);
    });
});

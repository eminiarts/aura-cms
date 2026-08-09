<?php

namespace Tests\Feature\Fields;

use Aura\Base\Contracts\EmbeddedLivewireComponent;
use Aura\Base\Contracts\MapsEmbeddedComponentParameters;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\LivewireComponent;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Services\EmbeddedComponentContext;
use Aura\Base\Services\EmbeddedComponentResolver;
use Aura\Base\Services\EmbeddedComponentSurface;
use Aura\Base\Traits\AuthorizesEmbeddedComponent;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Livewire;
use stdClass;

use function Pest\Livewire\livewire;

class Core12EmbeddedComponent extends Component implements EmbeddedLivewireComponent
{
    use AuthorizesEmbeddedComponent;

    public string $context = '';

    public string $fieldSlug = '';

    public string $label = '';

    public static int $mountCount = 0;

    /** @var array<string, mixed> */
    public array $nested = [];

    public int|string|null $resourceId = null;

    public string $resourceType = '';

    public function mount(): void
    {
        self::$mountCount++;
    }

    public function ping(): void {}

    public function render(): string
    {
        return <<<'HTML'
            <div data-embedded-context="{{ $context }}" data-embedded-field="{{ $fieldSlug }}">
                {{ $label }}|{{ $resourceId ?? 'new' }}|{{ data_get($nested, 'owner.id') ?? 'none' }}
            </div>
        HTML;
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
    public function render(): string
    {
        return '<div>Unbounded component</div>';
    }
}

class Core12MissingAuthorizationTraitComponent extends Component implements EmbeddedLivewireComponent
{
    public function render(): string
    {
        return '<div>Missing authorization trait</div>';
    }
}

class Core12ParameterMapper implements MapsEmbeddedComponentParameters
{
    public function map(EmbeddedComponentContext $context): array
    {
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

beforeEach(function () {
    $this->actingAs($this->user = createGlobalAdmin());

    Livewire::component('core12.embedded.edit', Core12EmbeddedComponent::class);
    Livewire::component('core12.embedded.view', Core12EmbeddedComponent::class);
    Livewire::component('core12.embedded.index', Core12EmbeddedComponent::class);
    Livewire::component('core12.embedded.fallback', Core12FallbackEmbeddedComponent::class);
    Livewire::component('core12.missing-authorization-trait', Core12MissingAuthorizationTraitComponent::class);
    Livewire::component('core12.unbounded', Core12UnboundedComponent::class);
});

function core12Field(array $overrides = []): array
{
    return array_replace(Core12EmbeddedResource::getFields()[0], $overrides);
}

describe('LivewireComponent field configuration', function () {
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
            ->and($definition->parameters)->toMatchArray([
                'resourceType' => Core12EmbeddedResource::class,
                'resourceId' => $resource->getKey(),
                'fieldSlug' => 'embedded_surface',
                'context' => $surface->value,
                'label' => 'Mapped '.$surface->value,
                'nested' => ['owner' => ['id' => $resource->getKey()]],
            ])
            ->and($definition->parameters)->not->toHaveKeys(['model', 'field'])
            ->and($definition->parameters['auraEmbeddedContext'])->toBeArray();
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
            ->and($definition->parameters['resourceId'])->toBeNull()
            ->and($definition->parameters['resourceType'])->toBe(Core12EmbeddedResource::class)
            ->and($definition->parameters)->not->toHaveKeys(['model', 'field']);

        Livewire::test($definition->alias, $definition->parameters)
            ->assertSee('Mapped edit|new|none');
    });

    test('uses a declared bounded fallback for a missing or unsupported context alias', function (array $aliases) {
        $resource = Core12EmbeddedResource::create(['title' => 'Fallback owner']);

        $definition = app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['component_aliases' => $aliases]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        );

        expect($definition)->not->toBeNull()
            ->and($definition->alias)->toBe('core12.embedded.fallback');
    })->with([
        'missing alias' => [[
            'view' => 'core12.does-not-exist',
            'fallback' => 'core12.embedded.fallback',
        ]],
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

        expect(app(EmbeddedComponentResolver::class)->resolve(
            field: core12Field(['parameter_mapper' => Core12UnsafeParameterMapper::class]),
            resource: $resource,
            surface: EmbeddedComponentSurface::View,
        ))->toBeNull();
    });

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

    test('renders an index page without per-row owner lookups', function () {
        foreach (range(1, 20) as $index) {
            Core12EmbeddedResource::create(['title' => 'Owner '.$index]);
        }

        $component = livewire(Table::class, [
            'query' => null,
            'model' => new Core12EmbeddedResource,
        ])->set('perPage', 100)
            ->assertSee('Mapped index');

        DB::enableQueryLog();
        DB::flushQueryLog();

        $component->call('$refresh');

        $ownerSelects = collect(DB::getQueryLog())->filter(function (array $query): bool {
            $sql = strtolower($query['query']);

            return str_contains($sql, 'from "posts"') || str_contains($sql, 'from `posts`');
        });

        DB::disableQueryLog();

        expect($ownerSelects)->toHaveCount(2);
    });
});

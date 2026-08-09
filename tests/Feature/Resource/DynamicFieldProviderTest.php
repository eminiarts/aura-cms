<?php

use Aura\Base\Contracts\ContextualFieldProvider;
use Aura\Base\Contracts\FieldProvider;
use Aura\Base\Exceptions\FieldProviderConflictException;
use Aura\Base\Facades\Aura;
use Aura\Base\FieldProviderContext;
use Aura\Base\FieldProviderMode;
use Aura\Base\FieldProviderRegistry;
use Aura\Base\Resource;

class DynamicFieldProviderResource extends Resource
{
    public static function getFields(): array
    {
        return [
            ['name' => 'Base', 'slug' => 'base', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Tail', 'slug' => 'tail', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class ResourceWithoutDynamicProviders extends Resource
{
    public static int $declarationCalls = 0;

    public static function getFields(): array
    {
        static::$declarationCalls++;

        return [
            ['name' => 'Untouched', 'slug' => 'untouched', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class SluglessDeclarativeFieldResource extends Resource
{
    public static function getFields(): array
    {
        return [
            ['name' => 'Legacy wrapper', 'type' => 'Aura\\Base\\Fields\\Panel', 'fields' => []],
        ];
    }
}

abstract class StaticFieldProvider implements FieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return [];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }
}

class AlphaAppendFieldProvider extends StaticFieldProvider
{
    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'Alpha', 'slug' => 'alpha', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class BetaAppendFieldProvider extends StaticFieldProvider
{
    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'Beta', 'slug' => 'beta', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class LowPriorityReplacementFieldProvider extends StaticFieldProvider
{
    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'Low priority', 'slug' => 'base', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class HighPriorityReplacementFieldProvider extends StaticFieldProvider
{
    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'High priority', 'slug' => 'base', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class EqualPriorityReplacementOne extends StaticFieldProvider
{
    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'One', 'slug' => 'base', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class EqualPriorityReplacementTwo extends StaticFieldProvider
{
    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'Two', 'slug' => 'base', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class TeamFieldProviderState
{
    public static int $fieldsCalls = 0;

    public static array $labels = [];

    public static int|string|null $teamId = null;

    public static int $versionCalls = 0;

    public static array $versions = [];

    public static function reset(): void
    {
        static::$fieldsCalls = 0;
        static::$labels = [1 => 'Team one', 2 => 'Team two'];
        static::$teamId = 1;
        static::$versionCalls = 0;
        static::$versions = [1 => 1, 2 => 1];
    }
}

class TeamFieldProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['team_id' => TeamFieldProviderState::$teamId];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        TeamFieldProviderState::$versionCalls++;

        return TeamFieldProviderState::$versions[$context->value('team_id')];
    }

    public function fields(FieldProviderContext $context): array
    {
        TeamFieldProviderState::$fieldsCalls++;

        return [
            [
                'name' => TeamFieldProviderState::$labels[$context->value('team_id')],
                'slug' => 'team_field',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['team_field'];
    }
}

class TransientFieldProvider extends StaticFieldProvider
{
    public function fields(FieldProviderContext $context): array
    {
        return [
            ['name' => 'Transient', 'slug' => 'transient', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

beforeEach(function () {
    ResourceWithoutDynamicProviders::$declarationCalls = 0;
    TeamFieldProviderState::reset();
    Resource::flushFieldCache();
});

it('registers multiple providers in deterministic priority order without affecting other resources', function () {
    Aura::registerFieldProvider(
        BetaAppendFieldProvider::class,
        resources: [DynamicFieldProviderResource::class],
        priority: 20,
    );
    Aura::registerFieldProvider(
        AlphaAppendFieldProvider::class,
        resources: [DynamicFieldProviderResource::class],
        priority: 10,
    );

    expect((new DynamicFieldProviderResource)->fieldsCollection()->pluck('slug')->all())
        ->toBe(['base', 'tail', 'alpha', 'beta'])
        ->and((new ResourceWithoutDynamicProviders)->fieldsCollection()->pluck('slug')->all())
        ->toBe(['untouched'])
        ->and((new ResourceWithoutDynamicProviders)->fieldsCollection()->pluck('slug')->all())
        ->toBe(['untouched'])
        ->and(ResourceWithoutDynamicProviders::$declarationCalls)->toBe(1)
        ->and((new SluglessDeclarativeFieldResource)->fieldsCollection()->all())
        ->toBe([['name' => 'Legacy wrapper', 'type' => 'Aura\\Base\\Fields\\Panel', 'fields' => []]]);
});

it('resolves replacement conflicts by explicit priority while preserving field position', function () {
    Aura::registerFieldProvider(
        HighPriorityReplacementFieldProvider::class,
        resources: [DynamicFieldProviderResource::class],
        mode: FieldProviderMode::Replace,
        priority: 20,
    );
    Aura::registerFieldProvider(
        LowPriorityReplacementFieldProvider::class,
        resources: [DynamicFieldProviderResource::class],
        mode: FieldProviderMode::Replace,
        priority: 10,
    );

    $fields = (new DynamicFieldProviderResource)->fieldsCollection();

    expect($fields->pluck('slug')->all())->toBe(['base', 'tail'])
        ->and($fields->firstWhere('slug', 'base')['name'])->toBe('High priority');
});

it('rejects ambiguous replacement conflicts at the same priority', function () {
    Aura::registerFieldProvider(
        EqualPriorityReplacementTwo::class,
        resources: [DynamicFieldProviderResource::class],
        mode: FieldProviderMode::Replace,
        priority: 10,
    );
    Aura::registerFieldProvider(
        EqualPriorityReplacementOne::class,
        resources: [DynamicFieldProviderResource::class],
        mode: FieldProviderMode::Replace,
        priority: 10,
    );

    expect(fn () => (new DynamicFieldProviderResource)->fieldsCollection())
        ->toThrow(FieldProviderConflictException::class, 'base');
});

it('versions provider output by explicit team context and invalidates it on demand', function () {
    Aura::registerFieldProvider(
        TeamFieldProvider::class,
        resources: [DynamicFieldProviderResource::class],
    );

    $resource = new DynamicFieldProviderResource;

    expect($resource->fieldBySlug('team_field')['name'])->toBe('Team one')
        ->and($resource->fieldsCollection()->last()['name'])->toBe('Team one')
        ->and(TeamFieldProviderState::$versionCalls)->toBe(1)
        ->and(TeamFieldProviderState::$fieldsCalls)->toBe(1);

    TeamFieldProviderState::$teamId = 2;

    expect($resource->fieldBySlug('team_field')['name'])->toBe('Team two')
        ->and(TeamFieldProviderState::$versionCalls)->toBe(2)
        ->and(TeamFieldProviderState::$fieldsCalls)->toBe(2);

    TeamFieldProviderState::$teamId = 1;
    TeamFieldProviderState::$labels[1] = 'Updated team one';
    TeamFieldProviderState::$versions[1] = 2;

    expect($resource->fieldBySlug('team_field')['name'])->toBe('Team one')
        ->and(TeamFieldProviderState::$versionCalls)->toBe(2)
        ->and(TeamFieldProviderState::$fieldsCalls)->toBe(2);

    Aura::flushFieldCache();

    expect($resource->fieldBySlug('team_field')['name'])->toBe('Updated team one')
        ->and(TeamFieldProviderState::$versionCalls)->toBe(3)
        ->and(TeamFieldProviderState::$fieldsCalls)->toBe(3);
});

it('restores boot providers and drops transient providers across a worker boundary', function () {
    $registry = new FieldProviderRegistry;
    app()->instance(FieldProviderRegistry::class, $registry);

    Aura::registerFieldProvider(
        TeamFieldProvider::class,
        resources: [DynamicFieldProviderResource::class],
    );
    $registry->captureBaselineState();

    expect((new DynamicFieldProviderResource)->fieldsCollection()->pluck('slug')->all())
        ->toBe(['base', 'tail', 'team_field']);

    Aura::registerFieldProvider(
        TransientFieldProvider::class,
        resources: [DynamicFieldProviderResource::class],
    );
    TeamFieldProviderState::$teamId = 2;

    Aura::flushState();

    $fields = (new DynamicFieldProviderResource)->fieldsCollection();

    expect($fields->pluck('slug')->all())->toBe(['base', 'tail', 'team_field'])
        ->and($fields->firstWhere('slug', 'team_field')['name'])->toBe('Team two')
        ->and(TeamFieldProviderState::$fieldsCalls)->toBe(2);
});

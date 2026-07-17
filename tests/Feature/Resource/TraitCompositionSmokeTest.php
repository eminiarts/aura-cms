<?php

use Aura\Base\BaseResource;
use Aura\Base\Resource;
use Aura\Base\Traits\AuraModelConfig;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Composition smoke tests (design amendment #11 + §5). AuraModelConfig is mixed
 * into BOTH Resource and BaseResource, so both host classes must keep the core
 * surface working through any decomposition. Plus a lifecycle-collision canary
 * pinning the pre-decomposition state that the package-prefixed concern trait
 * names (amendment #2) must preserve.
 */

// A trait whose Eloquent boot/initialize hooks are live, proving the naming
// mechanism that the prefixed concern names must avoid colliding with.
trait CompositionCanaryHostTrait
{
    public static int $bootCount = 0;

    public static int $initializeCount = 0;

    public static function bootCompositionCanaryHostTrait(): void
    {
        static::$bootCount++;
    }

    public function initializeCompositionCanaryHostTrait(): void
    {
        static::$initializeCount++;
    }
}

class CompositionResourceModel extends Resource
{
    public static ?string $slug = 'composition-resource';

    public static string $type = 'CompositionResource';

    public static function getFields()
    {
        return [
            ['name' => 'Headline', 'slug' => 'headline', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
        ];
    }
}

class CompositionBaseModel extends BaseResource
{
    public static ?string $slug = 'composition-base';

    public static string $type = 'CompositionBase';

    public static function getFields()
    {
        return [
            ['name' => 'Headline', 'slug' => 'headline', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
        ];
    }
}

class CompositionBareBaseModel extends BaseResource {}

class CompositionCanaryResource extends Resource
{
    use CompositionCanaryHostTrait;

    public static ?string $slug = 'composition-canary';

    public static string $type = 'CompositionCanary';
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('a Resource subclass exposes the core AuraModelConfig surface', function () {
    $model = new CompositionResourceModel;

    expect(CompositionResourceModel::getFields())->toBeArray()
        ->and(collect(CompositionResourceModel::getFields())->pluck('slug')->all())->toContain('headline')
        ->and(CompositionResourceModel::getSlug())->toBe('composition-resource')
        ->and(CompositionResourceModel::getType())->toBe('CompositionResource')
        ->and(method_exists($model, 'meta'))->toBeTrue()
        ->and(method_exists($model, 'team'))->toBeTrue()
        ->and($model->meta())->toBeInstanceOf(MorphMany::class)
        ->and($model->team())->toBeInstanceOf(BelongsTo::class);
});

test('a BaseResource subclass exposes the core AuraModelConfig surface', function () {
    $model = new CompositionBaseModel;

    expect(CompositionBaseModel::getFields())->toBeArray()
        ->and(collect(CompositionBaseModel::getFields())->pluck('slug')->all())->toContain('headline')
        ->and(CompositionBaseModel::getSlug())->toBe('composition-base')
        ->and(CompositionBaseModel::getType())->toBe('CompositionBase')
        ->and(method_exists($model, 'meta'))->toBeTrue()
        ->and(method_exists($model, 'team'))->toBeTrue()
        ->and($model->meta())->toBeInstanceOf(MorphMany::class)
        ->and($model->team())->toBeInstanceOf(BelongsTo::class);
});

test('a bare BaseResource returns the [] getFields default from the trait', function () {
    expect(CompositionBareBaseModel::getFields())->toBe([]);
});

test('canary: host boot/initialize Eloquent hooks are live (the collision risk is real)', function () {
    // A second instantiation must fire initialize{Trait} again — the mechanism a
    // future concern trait named e.g. "ResourceMeta" would hook into as
    // initializeResourceMeta(), colliding with a host method of the same name.
    new CompositionCanaryResource;
    $before = CompositionCanaryResource::$initializeCount;
    new CompositionCanaryResource;

    expect(CompositionCanaryResource::$initializeCount)->toBe($before + 1)
        ->and(CompositionCanaryResource::$bootCount)->toBeGreaterThanOrEqual(1);
});

test('canary: AuraModelConfig currently declares NO boot*/initialize* hook methods', function () {
    // Pins the pre-decomposition invariant: the aggregator trait contributes zero
    // Eloquent lifecycle hooks today (its basename yields bootAuraModelConfig /
    // initializeAuraModelConfig, neither of which exists). The package-prefixed
    // sub-trait names introduced by the decomposition MUST preserve this — an
    // unprefixed "ResourceMeta" would turn a host's bootResourceMeta() into a hook.
    $reflection = new ReflectionClass(AuraModelConfig::class);

    $hookMethods = collect($reflection->getMethods())
        ->map(fn (ReflectionMethod $m) => $m->getName())
        ->filter(fn (string $name) => preg_match('/^(boot|initialize)[A-Z]/', $name))
        ->values()
        ->all();

    expect($hookMethods)->toBe([])
        ->and(method_exists(Resource::class, 'bootAuraModelConfig'))->toBeFalse()
        ->and(method_exists(Resource::class, 'initializeAuraModelConfig'))->toBeFalse();
});

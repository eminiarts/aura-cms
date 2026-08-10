<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

class Core26PropertyActionResource extends Resource
{
    public array $actions = [
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public static bool $available = true;

    public static ?string $slug = 'core26-property-action';

    public static string $type = 'Core26PropertyAction';

    public function getActions(): array
    {
        $actions = parent::getActions();
        $actions['markReviewed']['conditional_logic'] = fn (): bool => self::$available;

        return $actions;
    }

    public static function getFields(): array
    {
        return [];
    }

    public function markReviewed(): void
    {
        $this->forceFill(['content' => 'reviewed'])->save();
    }
}

class Core26MethodActionResource extends Core26PropertyActionResource
{
    public array $actions = [
        'save' => ['label' => 'Forged property action', 'ability' => 'update'],
    ];

    public static ?string $slug = 'core26-method-action';

    public static string $type = 'Core26MethodAction';

    public function actions(): array
    {
        return [
            'markReviewed' => [
                'label' => 'Effective method action',
                'ability' => 'update',
            ],
        ];
    }
}

class Core26DeniedActionPolicy
{
    public static bool $allow = true;

    public function update(User $user, Core26PropertyActionResource $resource): bool
    {
        return self::$allow;
    }
}

class Core26UuidActionResource extends Resource
{
    public array $actions = [
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public static $customTable = true;

    public $incrementing = false;

    public static ?string $slug = 'core26-uuid-action';

    public static string $type = 'Core26UuidAction';

    public static bool $usesMeta = false;

    protected $fillable = ['id', 'title', 'content', 'status', 'user_id', 'team_id'];

    protected $keyType = 'string';

    protected $table = 'core26_uuid_actions';

    public static function getFields(): array
    {
        return [];
    }

    public function markReviewed(): void
    {
        $this->forceFill(['content' => 'reviewed'])->save();
    }
}

beforeEach(function () {
    Core26PropertyActionResource::$available = true;
    Core26DeniedActionPolicy::$allow = true;
});

afterEach(function () {
    Schema::dropIfExists('core26_uuid_actions');
});

function core26ActionResource(string $class = Core26PropertyActionResource::class): Resource
{
    $resource = $class::withoutGlobalScopes()->create([
        'title' => 'CORE-26 action',
        'type' => $class::getType(),
        'status' => 'publish',
        ...config('aura.teams') ? ['team_id' => auth()->user()->current_team_id] : [],
    ]);

    Aura::registerResources([$class]);
    Aura::registerRoutes($class::getSlug(), $class);
    Aura::clearRoutes();

    return $resource;
}

test('a property-declared action uses the rendered getActions allow-list', function () {
    $this->actingAs(createSuperAdmin());
    $resource = core26ActionResource();

    livewire(Edit::class, ['id' => $resource->getKey(), 'slug' => $resource->getSlug()])
        ->assertSee('Mark reviewed')
        ->call('singleAction', 'markReviewed')
        ->assertDispatched('notify')
        ->assertSuccessful();

    expect($resource->fresh()->content)->toBe('reviewed');
});

test('an overridden actions method is the source for rendering and dispatch', function () {
    $this->actingAs(createSuperAdmin());
    $resource = core26ActionResource(Core26MethodActionResource::class);

    livewire(Edit::class, ['id' => $resource->getKey(), 'slug' => $resource->getSlug()])
        ->assertSee('Effective method action')
        ->assertDontSee('Forged property action')
        ->call('singleAction', 'markReviewed')
        ->assertSuccessful();

    expect($resource->fresh()->content)->toBe('reviewed');
});

test('forged and stale action keys fail closed without invoking model methods', function () {
    $this->actingAs(createSuperAdmin());
    $resource = core26ActionResource();

    livewire(Edit::class, ['id' => $resource->getKey(), 'slug' => $resource->getSlug()])
        ->call('singleAction', 'save')
        ->assertForbidden();

    Core26PropertyActionResource::$available = false;

    livewire(Edit::class, ['id' => $resource->getKey(), 'slug' => $resource->getSlug()])
        ->call('singleAction', 'markReviewed')
        ->assertForbidden();

    expect($resource->fresh()->content)->not->toBe('reviewed');
});

test('a declared action is reauthorized against the current policy', function () {
    $this->actingAs(createAdmin());
    Gate::policy(Core26PropertyActionResource::class, Core26DeniedActionPolicy::class);
    $resource = core26ActionResource();

    $component = livewire(Edit::class, ['id' => $resource->getKey(), 'slug' => $resource->getSlug()]);
    Core26DeniedActionPolicy::$allow = false;

    $component
        ->call('singleAction', 'markReviewed')
        ->assertForbidden();

    expect($resource->fresh()->content)->not->toBe('reviewed');
});

test('single actions preserve string and UUID record keys', function () {
    $this->actingAs(createSuperAdmin());
    Schema::create('core26_uuid_actions', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('title')->nullable();
        $table->string('content')->nullable();
        $table->string('status')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->timestamps();
    });

    $id = (string) Str::uuid();
    $resource = Core26UuidActionResource::create([
        'id' => $id,
        'title' => 'UUID action',
        'status' => 'publish',
        'user_id' => auth()->id(),
        'team_id' => config('aura.teams') ? auth()->user()->current_team_id : null,
    ]);
    Aura::registerResources([Core26UuidActionResource::class]);

    livewire(Edit::class, ['id' => $id, 'slug' => Core26UuidActionResource::getSlug()])
        ->call('singleAction', 'markReviewed')
        ->assertSuccessful();

    expect($resource->fresh()->content)->toBe('reviewed');
});

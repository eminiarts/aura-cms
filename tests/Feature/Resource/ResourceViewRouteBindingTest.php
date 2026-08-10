<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Routing\ResourceViewRoute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Livewire;

class Core26BoundResource extends Resource
{
    use SoftDeletes;

    public static ?string $slug = 'core26-bound-resource';

    public static string $type = 'Core26BoundResource';

    public static function getFields(): array
    {
        return [];
    }

    public function getRouteKeyName(): string
    {
        return 'title';
    }

    public static function viewComponent(): string
    {
        return Core26BoundView::class;
    }
}

class Core26OtherResource extends Resource
{
    public static ?string $slug = 'core26-other-resource';

    public static string $type = 'Core26OtherResource';
}

class Core26DefaultViewRouteKeyResource extends Resource
{
    public static ?string $slug = 'core26-default-view-route-key';

    public static string $type = 'Core26DefaultViewRouteKeyResource';

    public static function getFields(): array
    {
        return [];
    }

    public function getRouteKeyName(): string
    {
        return 'title';
    }
}

class Core26BoundView extends Component
{
    public Core26BoundResource $resource;

    public function mount(Core26BoundResource $core26BoundResource): void
    {
        $this->resource = $core26BoundResource;
    }

    #[Layout('aura::components.layout.app')]
    public function render(): string
    {
        return '<div>Bound resource: '.e($this->resource->title).'</div>';
    }
}

class Core26WrongBoundView extends Component
{
    public function mount(Core26OtherResource $other): void {}
}

class Core26BroadResourceBoundView extends Component
{
    public function mount(Resource $resource): void {}
}

class Core26BroadModelBoundView extends Component
{
    public function mount(Model $model): void {}
}

class Core26DeniedViewPolicy
{
    public function view(User $user, Core26BoundResource $resource): bool
    {
        return false;
    }
}

function core26BoundResource(): Core26BoundResource
{
    $resource = Core26BoundResource::withoutGlobalScopes()->create([
        'title' => 'Typed route binding',
        'type' => Core26BoundResource::getType(),
        'status' => 'publish',
        ...config('aura.teams') ? ['team_id' => auth()->user()->current_team_id] : [],
    ]);

    Aura::registerResources([Core26BoundResource::class]);
    Aura::registerRoutes(Core26BoundResource::getSlug(), Core26BoundResource::class);
    Aura::clearRoutes();

    return $resource;
}

test('a custom view component receives its typed resource through the named route', function () {
    $this->actingAs(createSuperAdmin());
    $resource = core26BoundResource();
    $route = Route::getRoutes()->getByName('aura.core26-bound-resource.view');

    expect($route->parameterNames())->toBe(['core26BoundResource'])
        ->and(route('aura.core26-bound-resource.view', [$resource]))
        ->toEndWith('/admin/core26-bound-resource/'.rawurlencode((string) $resource->getRouteKey()))
        ->and($resource->viewUrl())->toBe(route('aura.core26-bound-resource.view', [$resource]))
        ->and($resource->globalSearchUrl())->toBe(route('aura.core26-bound-resource.view', [$resource]));

    $this->get(route('aura.core26-bound-resource.view', [$resource]))
        ->assertSuccessful()
        ->assertSee('Bound resource: Typed route binding');
});

test('table view actions redirect with the resource route key', function () {
    $this->actingAs(createSuperAdmin());
    $resource = core26BoundResource();

    Livewire::test(Table::class, ['query' => null, 'model' => new Core26BoundResource])
        ->call('action', ['action' => 'view', 'id' => $resource->getKey()])
        ->assertRedirect(route('aura.core26-bound-resource.view', [$resource]));
});

test('the default view keeps primary key urls when a resource overrides its route key', function () {
    $this->actingAs(createSuperAdmin());
    $resource = Core26DefaultViewRouteKeyResource::withoutGlobalScopes()->create([
        'title' => 'Default view custom route key',
        'type' => Core26DefaultViewRouteKeyResource::getType(),
        'status' => 'publish',
        ...config('aura.teams') ? ['team_id' => auth()->user()->current_team_id] : [],
    ]);

    Aura::registerResources([Core26DefaultViewRouteKeyResource::class]);
    Aura::registerRoutes(Core26DefaultViewRouteKeyResource::getSlug(), Core26DefaultViewRouteKeyResource::class);
    Aura::clearRoutes();

    $url = route('aura.core26-default-view-route-key.view', ['id' => $resource->getKey()]);

    expect(Route::getRoutes()->getByName('aura.core26-default-view-route-key.view')->parameterNames())
        ->toBe(['id'])
        ->and($resource->viewUrl())->toBe($url)
        ->and($resource->globalSearchUrl())->toBe($url);

    Livewire::test(Table::class, ['query' => null, 'model' => new Core26DefaultViewRouteKeyResource])
        ->call('action', ['action' => 'view', 'id' => $resource->getKey()])
        ->assertRedirect($url);

    $this->get($url)->assertSuccessful();
});

test('typed custom view routes return 404 for missing wrong-type and soft-deleted records', function () {
    $this->actingAs(createSuperAdmin());
    $resource = core26BoundResource();
    $other = Core26OtherResource::withoutGlobalScopes()->create([
        'title' => 'Wrong type',
        'type' => Core26OtherResource::getType(),
        'status' => 'publish',
        ...config('aura.teams') ? ['team_id' => auth()->user()->current_team_id] : [],
    ]);

    $this->get(route('aura.core26-bound-resource.view', [999999]))->assertNotFound();
    $this->get(route('aura.core26-bound-resource.view', [$other->getKey()]))->assertNotFound();

    $resource->delete();

    $this->get(route('aura.core26-bound-resource.view', [$resource->getKey()]))->assertNotFound();
});

test('typed custom view routes authorize the bound resource before mounting', function () {
    $this->actingAs(createAdmin());
    Gate::policy(Core26BoundResource::class, Core26DeniedViewPolicy::class);
    $resource = core26BoundResource();

    $this->get(route('aura.core26-bound-resource.view', [$resource]))->assertForbidden();
});

test('a custom component binding the wrong model class is rejected at registration', function () {
    expect(fn () => ResourceViewRoute::parameter(Core26BoundResource::class, Core26WrongBoundView::class))
        ->toThrow(InvalidArgumentException::class);
});

test('a custom component binding a broad parent model class is rejected at registration', function (string $component) {
    expect(fn () => ResourceViewRoute::parameter(Core26BoundResource::class, $component))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'resource parent' => [Core26BroadResourceBoundView::class],
    'eloquent model parent' => [Core26BroadModelBoundView::class],
]);

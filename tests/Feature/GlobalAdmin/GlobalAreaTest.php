<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\CreateResource;
use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\ResourceEditor;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Livewire\Table\TableMutationAuthorizer;
use Aura\Base\Navigation\Navigation;
use Aura\Base\Providers\AppServiceProvider;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Livewire\livewire;

class GlobalAreaEditor extends ResourceEditor
{
    public function mount($slug) {}

    public function render()
    {
        return '<div></div>';
    }
}

beforeEach(function () {
    $this->app->register(AppServiceProvider::class);
});

function useAuraArea(string $area): void
{
    $route = Route::getRoutes()->getByName($area === 'global' ? 'aura.global.user.index' : 'aura.user.index');
    $request = Request::create(route($route->getName()));
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);
    app()->instance('request', $request);
}

function areaNavigationSlugs(): array
{
    return array_values(Arr::where(Arr::dot(Aura::navigation()->all()), fn ($value, $key) => str_ends_with($key, '.slug')));
}

function globalAreaTableSnapshot(string $html): string
{
    preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);

    foreach ($matches[1] as $encoded) {
        $snapshot = html_entity_decode($encoded, ENT_QUOTES);
        if (str_contains(json_decode($snapshot, true)['memo']['name'], 'table')) {
            return $snapshot;
        }
    }

    throw new RuntimeException('No table snapshot rendered.');
}

test('navigation isolates areas across sessions and team switches without cache clearing', function () {
    $user = createSuperAdmin();
    config(['aura.global_admin.emails' => [$user->email]]);
    Navigation::add([
        ['name' => 'Team custom', 'slug' => 'team-custom', 'route' => false, 'sort' => 1],
        ['name' => 'Global custom', 'slug' => 'global-custom', 'area' => 'global', 'route' => false, 'sort' => 1],
    ]);

    $globalKey = null;
    for ($session = 0; $session < 2; $session++) {
        $this->actingAs($user->fresh());
        foreach (['team', 'global', 'team'] as $area) {
            useAuraArea($area);
            $slugs = areaNavigationSlugs();
            expect($slugs)->toContain('user', $area.'-custom');
            if ($area === 'team') {
                expect($slugs)->toContain('dashboard')
                    ->not->toContain('team', 'create_resource', 'plugins', 'global-custom');
                expect(in_array('settings', $slugs, true))->toBe(auth()->user()->isSuperAdmin());
            } else {
                expect($slugs)->toContain('create_resource', 'plugins')
                    ->not->toContain('dashboard', 'settings', 'role', 'team-custom');
                expect(in_array('team', $slugs, true))->toBe((bool) config('aura.teams'));
                $globalKey ??= Aura::navigationCacheKey();
                expect(Aura::navigationCacheKey())->toBe($globalKey);
            }
        }
        if (config('aura.teams')) {
            $user->switchTeam(foreignTeam());
        }
    }
});

test('every global route denies a team Super Admin', function () {
    $this->actingAs($user = createSuperAdmin());
    config(['aura.features.resource_editor' => true]);

    foreach (Route::getRoutes() as $route) {
        if (str_starts_with($route->getName() ?? '', 'aura.global.')) {
            $this->get(route($route->getName(), ['id' => $user->id, 'slug' => 'user']))->assertForbidden();
        }
    }
});

test('global tools deny Super Admin mounts and recheck the gate on actions', function (string $component, array $params, string $action) {
    config(['aura.features.resource_editor' => true]);
    $this->actingAs(createSuperAdmin());
    livewire($component, $params)->assertForbidden();

    $this->actingAs(createGlobalAdmin());
    $mounted = livewire($component, $params)->assertOk();
    config(['aura.global_admin.emails' => []]);
    $mounted->call($action)->assertForbidden();
})->with([
    [CreateResource::class, [], 'closemodal'],
    [GlobalAreaEditor::class, ['slug' => 'user'], 'checkAuthorization'],
    [PluginsPage::class, [], 'getInstalledPackages'],
]);

test('the global index and tools work without a current team', function () {
    $this->actingAs(createGlobalAdmin());
    $this->get(route('aura.global.index'))->assertRedirect(route(config('aura.teams') ? 'aura.global.team.index' : 'aura.global.user.index'));
    $this->get(route('aura.global.user.index'))->assertOk()->assertSee('Global Admin');
    $this->get(route('aura.global.plugins'))->assertOk();
    livewire(CreateResource::class)->assertOk();
    if (config('aura.teams')) {
        $this->get(route('aura.dashboard'))->assertRedirect(route('aura.global.index'));
        $this->get(route('aura.global.team.index'))->assertOk();
    }
});

test('Global Admin user queries and resource links follow the URL area', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Membership isolation requires teams.');
    }
    $actor = createSuperAdmin();
    config(['aura.global_admin.emails' => [$actor->email]]);
    $own = soleMemberOf($actor->currentTeam);
    $foreign = soleMemberOf(foreignTeam());
    $orphan = User::factory()->create();
    $this->actingAs($actor);

    foreach (['team', 'global', 'team'] as $area) {
        $response = $this->get(route($area === 'global' ? 'aura.global.user.index' : 'aura.user.index'))->assertOk();
        $ids = (new User)->indexQuery(User::query())->pluck('id');
        expect($ids)->toContain($own->id);
        expect($ids->contains($foreign->id))->toBe($area === 'global');
        expect($ids->contains($orphan->id))->toBe($area === 'global');
        expect($own->editUrl())->toBe(route($area === 'global' ? 'aura.global.user.edit' : 'aura.user.edit', $own->id));
        if ($area === 'team') {
            $response->assertDontSee($foreign->email)->assertDontSee($orphan->email);
            expect(fn () => app(TableMutationAuthorizer::class)->authorizeBulk(
                (new User)->indexQuery(User::query()), 'delete', ['delete' => ['ability' => 'delete']], [$foreign->id],
            ))->toThrow(ValidationException::class);
            $this->get(route('aura.user.view', $foreign->id))->assertForbidden();
            $this->get(route('aura.user.edit', $foreign->id))->assertForbidden();
        } else {
            $response->assertSee($foreign->email)->assertSee($orphan->email);
            expect(app(TableMutationAuthorizer::class)->authorizeBulk(
                (new User)->indexQuery(User::query()), 'delete', ['delete' => ['ability' => 'delete']], [$foreign->id],
            )->modelKeys())->toBe([$foreign->id]);
            $this->get(route('aura.global.user.view', $foreign->id))->assertOk();
            $this->get(route('aura.global.user.edit', $foreign->id))->assertOk();
        }
    }
});

test('Livewire updates preserve the global area through the checksummed original path', function () {
    $this->actingAs(createGlobalAdmin());
    $orphan = User::factory()->create();
    $page = $this->get(route('aura.global.user.index'))->assertOk();
    $snapshot = globalAreaTableSnapshot($page->getContent());
    expect(json_decode($snapshot, true)['memo']['path'])->toContain('/global/user');

    $this->postJson(Livewire::getUpdateUri(), ['components' => [[
        'snapshot' => $snapshot,
        'updates' => [],
        'calls' => [['method' => '$refresh', 'params' => []]],
    ]]], ['X-Livewire' => 'true'])->assertOk()->assertSee($orphan->email);
    expect(Aura::area())->toBe('global');
    $this->postJson(Livewire::getUpdateUri(), ['components' => [[
        'snapshot' => $snapshot,
        'updates' => [],
        'calls' => [['method' => 'action', 'params' => [['action' => 'view', 'id' => $orphan->id]]]],
    ]]], ['X-Livewire' => 'true'])->assertOk()->assertJsonPath('components.0.effects.redirect', route('aura.global.user.view', $orphan->id));
});

test('the area resolver respects route defaults, host routes and non-HTTP work', function () {
    expect(Aura::area())->toBeNull();
    useAuraArea('team');
    request()->headers->set('X-Livewire', 'true');
    expect(Aura::area())->toBe('team');
    useAuraArea('global');
    expect(Aura::area())->toBe('global');

    // Livewire updates take the area from the route the page was rendered on
    // (path, method, scheme), so a host route that opts in keeps it across
    // interactions, also when the app is served from a subdirectory.
    Route::get('/operators/users', fn () => null)->defaults('aura_area', 'global');
    Route::get('/operators/run', fn () => null);
    Route::post('/operators/run', fn () => null)->defaults('aura_area', 'global');
    Route::get('/operators/secure', ['https', 'uses' => fn () => null])->defaults('aura_area', 'global');
    Route::domain('ops.example.test')->get('/operators/domain', fn () => null)->defaults('aura_area', 'global');
    $updateArea = function (string $url, string $path, string $method, array $server = []): ?string {
        $snapshot = json_encode(['memo' => ['path' => $path, 'method' => $method]]);
        $request = Request::create($url, 'POST', ['components' => [['snapshot' => $snapshot]]], server: $server);
        $request->headers->set('X-Livewire', 'true');
        $route = (new Illuminate\Routing\Route(['POST'], 'livewire/update', fn () => null))->name('livewire.update');
        $request->setRouteResolver(fn () => $route);
        app()->instance('request', $request);

        return Aura::area();
    };
    $cases = [
        ['admin/global', 'GET', 'global'], ['admin/global/user', 'GET', 'global'],
        ['admin/global-other/user', 'GET', 'team'], ['admin/user', 'GET', 'team'],
        ['operators/users', 'GET', 'global'], ['operators/run', 'GET', 'team'],
        ['operators/run', 'POST', 'global'], ['missing', 'GET', 'team'],
    ];
    foreach (['' => [], '/subdir' => ['SCRIPT_NAME' => '/subdir/index.php', 'SCRIPT_FILENAME' => public_path('index.php')]] as $base => $server) {
        foreach ($cases as [$path, $method, $area]) {
            expect($updateArea("http://localhost{$base}/livewire/update", $path, $method, $server))->toBe($area, "{$base}/{$path} {$method}");
        }
    }
    expect($updateArea('http://localhost/livewire/update', 'operators/secure', 'GET'))->toBe('team')
        ->and($updateArea('https://localhost/livewire/update', 'operators/secure', 'GET'))->toBe('global')
        ->and($updateArea('http://localhost/livewire/update', 'operators/domain', 'GET'))->toBe('team')
        ->and($updateArea('http://ops.example.test/livewire/update', 'operators/domain', 'GET'))->toBe('global');

    // Behind a trusted proxy that strips a path prefix.
    Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);
    try {
        expect($updateArea('http://localhost/livewire/update', 'operators/users', 'GET', ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_PREFIX' => '/cms']))->toBe('global');
    } finally {
        Request::setTrustedProxies([], -1);
    }
});

test('a team resource cannot claim the reserved global path', function () {
    expect(fn () => Aura::registerRoutes(config('aura.global_admin.path'), User::class))
        ->toThrow(LogicException::class, 'reserved');
});

test('the Visit row action enters a team without granting Membership', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Visitation requires teams.');
    }
    $actor = createGlobalAdmin();
    $foreign = foreignTeam();
    $this->actingAs($actor);
    livewire(Table::class, ['model' => new Team])
        ->call('action', ['action' => 'visitAction', 'id' => $foreign->id])
        ->assertRedirect(route('aura.dashboard'));
    expect($actor->fresh()->current_team_id)->toBe($foreign->id)
        ->and($actor->teams()->whereKey($foreign->id)->exists())->toBeFalse();
});

test('global resource Livewire updates still require the Global Admin gate', function () {
    $this->actingAs(createGlobalAdmin());
    $page = $this->get(route('aura.global.user.index'))->assertOk();
    $snapshot = globalAreaTableSnapshot($page->getContent());
    config(['aura.global_admin.emails' => []]);

    $this->postJson(Livewire::getUpdateUri(), ['components' => [[
        'snapshot' => $snapshot,
        'updates' => [],
        'calls' => [['method' => '$refresh', 'params' => []]],
    ]]], ['X-Livewire' => 'true'])->assertForbidden();
});

class GlobalOnlyAreaResource extends \Aura\Base\Resource
{
    public static array $areas = ['global'];

    public static ?string $slug = 'global-only';

    public function navigation()
    {
        return ['name' => 'Global only', 'slug' => static::$slug, 'route' => $this->indexUrl(), 'sort' => 1];
    }
}

test('resource membership controls routes and custom navigation independently of group names', function () {
    $this->actingAs(createGlobalAdmin());
    Aura::registerResources([GlobalOnlyAreaResource::class]);
    Aura::registerRoutes('global-only', GlobalOnlyAreaResource::class);
    Route::getRoutes()->refreshNameLookups();
    expect(Route::has('aura.global-only.index'))->toBeFalse();
    foreach (['index', 'create', 'edit', 'view'] as $action) {
        $route = Route::getRoutes()->getByName('aura.global.global-only.'.$action);
        expect($route->defaults)->toMatchArray(['slug' => 'global-only', 'aura_area' => 'global'])
            ->and($route->middleware())->toContain('can:AuraGlobalAdmin');
    }
    useAuraArea('team');
    expect(areaNavigationSlugs())->not->toContain('global-only')
        ->and((new GlobalOnlyAreaResource)->indexUrl())->toBe(route('aura.global.global-only.index'));
    useAuraArea('global');
    expect(areaNavigationSlugs())->toContain('global-only')
        ->and((new Role)->createUrl())->toBe(route('aura.role.create'));
});

test('resource registration also rejects the reserved team slug', function () {
    config(['aura.global_admin.path' => 'user']);
    expect(fn () => Aura::registerResources([User::class]))->toThrow(LogicException::class, 'reserved');
});

test('team Super Admins cannot manage Global Admin accounts or global-only resources', function () {
    $this->actingAs($actor = createSuperAdmin());
    $globalAdmin = config('aura.teams') ? soleMemberOf($actor->currentTeam) : User::factory()->create();
    config(['aura.global_admin.emails' => [$globalAdmin->email]]);

    expect($actor->can('view', $globalAdmin))->toBeTrue()
        ->and($actor->can('update', $globalAdmin))->toBeFalse()
        ->and($actor->can('delete', $globalAdmin))->toBeFalse()
        ->and($actor->can('viewAny', new GlobalOnlyAreaResource))->toBeFalse()
        ->and($actor->can('update', new GlobalOnlyAreaResource))->toBeFalse();
    $this->get(route('aura.user.edit', $globalAdmin->id))->assertForbidden();

    config(['aura.global_admin.emails' => [$globalAdmin->email, $actor->email]]);
    expect($actor->can('update', $globalAdmin))->toBeTrue()
        ->and($actor->can('viewAny', new GlobalOnlyAreaResource))->toBeTrue();
});

test('a visiting Global Admin stays signed in on team pages', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Visitation requires teams.');
    }
    $globalAdmin = createGlobalAdmin();
    $this->actingAs($globalAdmin);
    $globalAdmin->switchTeam(foreignTeam());

    // Resolve the identity from the session the way a fresh request does,
    // through Aura's User model as installed apps do.
    config(['auth.providers.users.model' => User::class]);
    auth()->forgetGuards();
    useAuraArea('team');
    session()->put(auth()->guard()->getName(), $globalAdmin->id);

    expect(auth()->user()?->getKey())->toBe($globalAdmin->id);
});

test('an open editor re-checks authorization when saving', function () {
    $this->actingAs($actor = createSuperAdmin());
    $member = config('aura.teams') ? soleMemberOf($actor->currentTeam) : User::factory()->create();
    $editor = livewire(Edit::class, ['slug' => 'user', 'id' => $member->id])->assertOk();

    config(['aura.global_admin.emails' => [$member->email]]);

    $editor->call('save')->assertForbidden();
});

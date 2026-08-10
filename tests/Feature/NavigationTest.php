<?php

use Aura\Base\Facades\Aura;
use Aura\Base\HookManager;
use Aura\Base\Navigation\Navigation as NavigationRegistry;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\VersionedCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;

/**
 * @template TStore of ArrayStore
 *
 * @param  class-string<TStore>  $storeClass
 * @return TStore
 */
function serializedNavigationArrayStore(string $storeClass = ArrayStore::class): ArrayStore
{
    $constructor = new ReflectionMethod(ArrayStore::class, '__construct');

    if ($constructor->getNumberOfParameters() === 1) {
        return new $storeClass(serializesValues: true);
    }

    return new $storeClass(serializesValues: true, serializableClasses: false);
}

function serializedNavigationCacheRepository(): Repository
{
    return new Repository(serializedNavigationArrayStore());
}

/** @return list<string> */
function navigationArrayStoreKeys(ArrayStore $store): array
{
    $property = new ReflectionProperty(ArrayStore::class, 'storage');

    /** @var array<string, mixed> $storage */
    $storage = $property->getValue($store);

    return array_keys($storage);
}

/** @return array<string, mixed> */
function navigationArrayStoreValues(ArrayStore $store): array
{
    return collect(navigationArrayStoreKeys($store))
        ->mapWithKeys(fn (string $key): array => [$key => $store->get($key)])
        ->all();
}

/** @return list<array{resources: list<string>}> */
function navigationStructuralCachePayloads(ArrayStore $store): array
{
    return collect(navigationArrayStoreValues($store))
        ->filter(fn (mixed $value): bool => is_array($value)
            && array_keys($value) === ['resources']
            && is_array($value['resources']))
        ->values()
        ->all();
}

class InterleavingNavigationArrayStore extends ArrayStore
{
    private ?Closure $beforeNavigationPut = null;

    private bool $interleaved = false;

    public function beforeNextNavigationPut(Closure $callback): void
    {
        $this->beforeNavigationPut = $callback;
        $this->interleaved = false;
    }

    public function put($key, $value, $seconds)
    {
        if (! $this->interleaved
            && $this->beforeNavigationPut
            && (str_starts_with($key, 'aura.navigation.') || str_starts_with($key, 'aura.cache.value.'))) {
            $this->interleaved = true;
            ($this->beforeNavigationPut)();
        }

        return parent::put($key, $value, $seconds);
    }
}

class CachedPolicyNavigationHook
{
    public static int $invocations = 0;

    public static function apply(Collection $navigation): Collection
    {
        self::$invocations++;

        return $navigation->push(customNonResourceNavigationItem('CachedPolicyPage', extra: [
            'policy' => NavigationRegistry::policy(User::GLOBAL_ADMIN_GATE),
        ]));
    }
}

class AuthContextNavigationHook
{
    public static ?int $allowedTeamId = null;

    public static int|string|null $allowedUserId = null;

    public static int $invocations = 0;

    public static function apply(Collection $navigation): Collection
    {
        self::$invocations++;

        if (auth()->id() !== self::$allowedUserId
            || (self::$allowedTeamId !== null && auth()->user()?->current_team_id !== self::$allowedTeamId)) {
            return $navigation;
        }

        return $navigation->push(customNonResourceNavigationItem('AuthContextPage'));
    }
}

function customNavigationItem(string $resource, string $group = 'Custom Group', array $extra = []): array
{
    return array_merge([
        'icon' => '',
        'resource' => $resource,
        'type' => $resource,
        'name' => $resource,
        'slug' => str($resource)->slug()->toString(),
        'sort' => 100,
        'group' => $group,
        'route' => '#',
        'dropdown' => false,
        'showInNavigation' => true,
    ], $extra);
}

function customNonResourceNavigationItem(string $name, string $group = 'Custom Group', array $extra = []): array
{
    $item = customNavigationItem($name, $group, $extra);
    unset($item['resource']);

    return $item;
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new NavigationModel);
});

class NavigationModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';
}

class UserAwareBadgeNavigationModel extends Resource
{
    public static $pluralName = 'User Aware Badge Navigation Models';

    public static ?string $slug = 'user-aware-badge-navigation-model';

    public static string $type = 'UserAwareBadgeNavigationModel';

    public function getBadge(): int|string|null
    {
        return auth()->id();
    }

    public function getIndexRoute(): string
    {
        return '#';
    }
}

class TeamScopedBadgeNavigationModel extends Resource
{
    public static $pluralName = 'Team Scoped Badge Navigation Models';

    public static ?string $slug = 'team-scoped-badge-navigation-model';

    public static string $type = 'TeamScopedBadgeNavigationModel';

    public function getBadge(): int
    {
        return static::query()->count();
    }

    public function getIndexRoute(): string
    {
        return '#';
    }
}

class NavigationHiddenModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    protected static bool $showInNavigation = false;
}

class GroupedNavigationModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    protected static ?string $group = 'Custom Group';
}
class DropdownNavigationModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    protected static $dropdown = 'Custom Dropdown';

    protected static ?string $group = 'Custom Group';
}

test('navigation item is visible', function () {
    Aura::registerResources([
        NavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new NavigationModel)->pluralName())->toBe('NavigationModels');

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Resources'])->firstWhere('resource', 'NavigationModel');

    // Assert that an item was found.
    expect($item)->not->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertSee('NavigationModels');
});

test('navigation item can be hidden', function () {
    Aura::registerResources([
        NavigationHiddenModel::class,
    ]);

    $nav = Aura::navigation();

    Aura::clear();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertDontSee('NavigationModels');

});

test('navigation item is hidden when the Role has no access to it', function () {
    Cache::swap(serializedNavigationCacheRepository());

    Aura::registerResources([
        NavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new NavigationModel)->pluralName())->toBe('NavigationModels');

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Resources'])->firstWhere('resource', 'NavigationModel');

    // Assert that an item was found.
    expect($item)->not->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertSee('NavigationModels');

    // Create a role with no access to the resource
    $this->actingAs(createAdmin());

    // Visit Dashboard and assert that the item is not visible.
    $this->get(route('aura.dashboard'))
        ->assertDontSee('NavigationModels');

});

test('navigation auth callbacks are deferred until an authenticated navigation request', function () {
    $evaluatedUserIds = [];
    $allowedUserId = $this->user->getAuthIdentifier();

    NavigationRegistry::add(
        [customNonResourceNavigationItem('DeferredPage')],
        function () use (&$evaluatedUserIds, $allowedUserId): bool {
            $evaluatedUserIds[] = auth()->id();

            return auth()->id() === $allowedUserId;
        },
    );

    expect($evaluatedUserIds)->toBe([]);

    auth()->logout();

    expect(Aura::navigation())->toBeEmpty()
        ->and($evaluatedUserIds)->toBe([]);

    $this->actingAs($this->user);

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('DeferredPage')
        ->and($evaluatedUserIds)->toBe([$allowedUserId]);

    $deniedUser = createAdmin();
    $this->actingAs($deniedUser);

    expect(Aura::navigation())->not->toHaveKey('Custom Group')
        ->and($evaluatedUserIds)->toBe([$allowedUserId, $deniedUser->getAuthIdentifier()]);
});

test('console and queue boot never evaluate navigation auth callbacks', function () {
    $callbackInvocations = 0;
    auth()->logout();

    NavigationRegistry::add(
        [customNonResourceNavigationItem('QueuePolicyPage')],
        function () use (&$callbackInvocations): bool {
            $callbackInvocations++;

            return true;
        },
    );

    $job = Mockery::mock(Job::class);
    $job->shouldReceive('payload')->andReturn([]);
    Event::dispatch(new JobProcessing('sync', $job));

    expect(app()->runningInConsole())->toBeTrue()
        ->and($callbackInvocations)->toBe(0)
        ->and(Aura::navigation())->toBeEmpty()
        ->and($callbackInvocations)->toBe(0);
});

test('non resource navigation policies use the current authenticated user', function () {
    Cache::swap(serializedNavigationCacheRepository());
    $allowedUser = createGlobalAdmin();
    $deniedUser = createAdmin();

    NavigationRegistry::add([
        customNonResourceNavigationItem('PolicyPage', extra: [
            'policy' => NavigationRegistry::policy(User::GLOBAL_ADMIN_GATE),
        ]),
    ]);

    $this->actingAs($allowedUser);

    $allowedItem = collect(Aura::navigation()['Custom Group'])->firstWhere('type', 'PolicyPage');

    expect($allowedItem)
        ->not->toBeNull()
        ->not->toHaveKey('policy');

    $this->actingAs($deniedUser);

    expect(Aura::navigation())->not->toHaveKey('Custom Group');

    $this->actingAs($allowedUser);

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('PolicyPage');
});

test('request local hook definitions are reauthorized after a fresh application container', function () {
    $cache = serializedNavigationCacheRepository();
    Cache::swap($cache);
    CachedPolicyNavigationHook::$invocations = 0;
    app('hook_manager')->addHook('navigation', [CachedPolicyNavigationHook::class, 'apply']);
    $allowedUser = createGlobalAdmin();
    $deniedUser = createAdmin();
    $this->actingAs($allowedUser);

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('CachedPolicyPage')
        ->and(CachedPolicyNavigationHook::$invocations)->toBe(1);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($deniedUser);
    Aura::fake();
    app('hook_manager')->addHook('navigation', [CachedPolicyNavigationHook::class, 'apply']);

    expect(Aura::navigation())->not->toHaveKey('Custom Group')
        ->and(CachedPolicyNavigationHook::$invocations)->toBe(2);

    $this->actingAs($allowedUser);

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('CachedPolicyPage')
        ->and(CachedPolicyNavigationHook::$invocations)->toBe(3);
});

test('user aware resource badges are rebuilt after a serialized cache read in a fresh container', function () {
    $store = serializedNavigationArrayStore();
    $cache = new Repository($store);
    Cache::swap($cache);
    Aura::registerResources([UserAwareBadgeNavigationModel::class]);
    $firstUser = createGlobalAdmin();
    $secondUser = createGlobalAdmin();
    $this->actingAs($firstUser);

    $firstBadge = collect(Aura::navigation()['Resources'])
        ->firstWhere('resource', UserAwareBadgeNavigationModel::class)['badge'];

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($secondUser);
    Aura::fake();
    Aura::registerResources([UserAwareBadgeNavigationModel::class]);

    $secondBadge = collect(Aura::navigation()['Resources'])
        ->firstWhere('resource', UserAwareBadgeNavigationModel::class)['badge'];
    $structuralPayloads = navigationStructuralCachePayloads($store);

    expect($firstBadge)->toBe($firstUser->id)
        ->and($secondBadge)->toBe($secondUser->id)
        ->and($structuralPayloads)->toHaveCount(1)
        ->and($structuralPayloads[0]['resources'])->toContain(UserAwareBadgeNavigationModel::class);
});

test('team scoped resource badges are rebuilt after same user switches teams', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team-scoped badges require teams enabled.');
    }

    Cache::swap(serializedNavigationCacheRepository());
    $user = createSuperAdmin();
    $firstTeam = Team::findOrFail($user->current_team_id);
    $secondTeam = Team::create([
        'name' => 'Second Badge Team',
        'user_id' => $user->id,
    ]);
    $this->actingAs($user);

    TeamScopedBadgeNavigationModel::withoutGlobalScopes()->create([
        'team_id' => $firstTeam->id,
        'type' => TeamScopedBadgeNavigationModel::$type,
        'title' => 'First team one',
    ]);
    TeamScopedBadgeNavigationModel::withoutGlobalScopes()->create([
        'team_id' => $firstTeam->id,
        'type' => TeamScopedBadgeNavigationModel::$type,
        'title' => 'First team two',
    ]);
    TeamScopedBadgeNavigationModel::withoutGlobalScopes()->create([
        'team_id' => $secondTeam->id,
        'type' => TeamScopedBadgeNavigationModel::$type,
        'title' => 'Second team one',
    ]);
    Aura::registerResources([TeamScopedBadgeNavigationModel::class]);

    $badge = fn (): int => collect(Aura::navigation()['Resources'])
        ->firstWhere('resource', TeamScopedBadgeNavigationModel::class)['badge'];

    expect($user->switchTeam($firstTeam))->toBeTrue()
        ->and($badge())->toBe(2)
        ->and($user->switchTeam($secondTeam))->toBeTrue()
        ->and($badge())->toBe(1)
        ->and($user->switchTeam($firstTeam))->toBeTrue()
        ->and($badge())->toBe(2);
});

test('authenticated static hooks rebuild membership for each user', function () {
    $store = serializedNavigationArrayStore();
    Cache::swap(new Repository($store));
    $allowedUser = createGlobalAdmin();
    $deniedUser = createGlobalAdmin();
    AuthContextNavigationHook::$allowedUserId = $allowedUser->id;
    AuthContextNavigationHook::$allowedTeamId = null;
    AuthContextNavigationHook::$invocations = 0;
    app('hook_manager')->addHook('navigation', [AuthContextNavigationHook::class, 'apply']);
    $this->actingAs($allowedUser);

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('AuthContextPage');

    $this->actingAs($deniedUser);
    $structuralPayloads = navigationStructuralCachePayloads($store);

    expect(Aura::navigation())->not->toHaveKey('Custom Group')
        ->and(AuthContextNavigationHook::$invocations)->toBe(2)
        ->and($structuralPayloads)->toHaveCount(1);
});

test('long workers rebuild static hooks across team switches and guest login cycles', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team switching is a teams-on navigation concern.');
    }

    Cache::swap(serializedNavigationCacheRepository());
    $user = createSuperAdmin();
    $allowedTeam = Team::findOrFail($user->current_team_id);
    $deniedTeam = Team::create([
        'name' => 'Denied Hook Team',
        'user_id' => $user->id,
    ]);
    $this->actingAs($user);
    AuthContextNavigationHook::$allowedUserId = $user->id;
    AuthContextNavigationHook::$allowedTeamId = $allowedTeam->id;
    AuthContextNavigationHook::$invocations = 0;
    app('hook_manager')->addHook('navigation', [AuthContextNavigationHook::class, 'apply']);

    expect($user->switchTeam($allowedTeam))->toBeTrue()
        ->and(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('AuthContextPage')
        ->and(AuthContextNavigationHook::$invocations)->toBe(1)
        ->and($user->switchTeam($deniedTeam))->toBeTrue()
        ->and(Aura::navigation())->not->toHaveKey('Custom Group')
        ->and(AuthContextNavigationHook::$invocations)->toBe(2);

    auth()->logout();

    expect(Aura::navigation())->toBeEmpty()
        ->and(AuthContextNavigationHook::$invocations)->toBe(2);

    auth()->login($user);

    expect($user->switchTeam($allowedTeam))->toBeTrue()
        ->and(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('AuthContextPage')
        ->and(AuthContextNavigationHook::$invocations)->toBe(3);
});

test('navigation policies are reevaluated after a team switch', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team switching is a teams-on navigation concern.');
    }

    Cache::swap(serializedNavigationCacheRepository());
    $user = createSuperAdmin();
    $allowedTeam = Team::findOrFail($user->current_team_id);
    $deniedTeam = Team::create([
        'name' => 'Denied Navigation Team',
        'user_id' => $user->id,
    ]);
    $this->actingAs($user);

    Gate::define('view-navigation-in-team', function (User $actor, int $teamId): bool {
        return (int) $actor->current_team_id === $teamId;
    });

    NavigationRegistry::add([
        customNonResourceNavigationItem('TeamPolicyPage', extra: [
            'policy' => NavigationRegistry::policy('view-navigation-in-team', $allowedTeam->id),
        ]),
    ]);

    expect($user->switchTeam($allowedTeam))->toBeTrue()
        ->and(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('TeamPolicyPage')
        ->and($user->switchTeam($deniedTeam))->toBeTrue()
        ->and(Aura::navigation())->not->toHaveKey('Custom Group')
        ->and($user->switchTeam($allowedTeam))->toBeTrue()
        ->and(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('TeamPolicyPage');
});

test('navigation policies follow impersonation changes in the active guard', function () {
    Cache::swap(serializedNavigationCacheRepository());
    $globalAdmin = createGlobalAdmin();
    $member = User::factory()->create();
    $this->actingAs($globalAdmin);

    NavigationRegistry::add([
        customNonResourceNavigationItem('ImpersonationPolicyPage', extra: [
            'policy' => NavigationRegistry::policy(User::GLOBAL_ADMIN_GATE),
        ]),
    ]);

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('ImpersonationPolicyPage');

    $member->impersonateAction();

    expect(auth()->id())->toBe($member->id)
        ->and(Aura::navigation())->not->toHaveKey('Custom Group');

    expect(app(ImpersonateManager::class)->leave())->toBeTrue()
        ->and(auth()->id())->toBe($globalAdmin->id)
        ->and(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('ImpersonationPolicyPage');
});

test('hidden dropdown children and their empty groups are removed after authorization', function () {
    $user = createGlobalAdmin();
    $this->actingAs($user);

    NavigationRegistry::add([
        customNonResourceNavigationItem('AllowedChild', extra: [
            'dropdown' => 'Policy Dropdown',
            'policy' => NavigationRegistry::policy(User::GLOBAL_ADMIN_GATE),
        ]),
        customNonResourceNavigationItem('DeniedChild', extra: [
            'dropdown' => 'Policy Dropdown',
            'policy' => NavigationRegistry::policy('missing-navigation-ability'),
        ]),
        customNonResourceNavigationItem('OnlyDeniedChild', 'Empty Policy Group', [
            'dropdown' => 'Empty Dropdown',
            'policy' => NavigationRegistry::policy('missing-navigation-ability'),
        ]),
    ]);

    $navigation = Aura::navigation();
    $dropdown = collect($navigation['Custom Group'])->firstWhere('dropdown', 'Policy Dropdown');

    expect(collect($dropdown['items'])->pluck('type')->all())->toBe(['AllowedChild'])
        ->and($navigation)->not->toHaveKey('Empty Policy Group');
});

test('navigation policy payloads never execute forged callbacks', function () {
    $callbackInvocations = 0;

    NavigationRegistry::add([
        customNonResourceNavigationItem('ForgedCallbackPage', extra: [
            'policy' => [
                'ability' => function () use (&$callbackInvocations): bool {
                    $callbackInvocations++;

                    return true;
                },
            ],
        ]),
        customNavigationItem('MissingNavigationResource', 'Invalid Resource Group'),
    ]);

    expect(Aura::navigation())->not->toHaveKey('Custom Group')
        ->not->toHaveKey('Invalid Resource Group')
        ->and($callbackInvocations)->toBe(0);
});

test('role permission changes invalidate warmed navigation', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);
    $limitedUser = createAdmin();
    $this->actingAs($limitedUser);
    $navigationResources = fn () => Aura::navigation()
        ->flatMap(fn (Collection $items): Collection => $items)
        ->pluck('resource');

    expect($navigationResources())->not->toContain(NavigationModel::class);

    $role = Role::withoutGlobalScopes()->findOrFail($limitedUser->roles()->firstOrFail()->id);
    $role->update([
        'permissions' => array_merge($role->permissions ?? [], [
            'viewAny-navmodel' => true,
        ]),
    ]);

    expect($navigationResources())->toContain(NavigationModel::class);
});

test('rolled back role permission changes do not poison rebuilt navigation', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);
    $limitedUser = createAdmin();
    $this->actingAs($limitedUser);
    $navigationResources = fn () => Aura::navigation()
        ->flatMap(fn (Collection $items): Collection => $items)
        ->pluck('resource');

    expect($navigationResources())->not->toContain(NavigationModel::class);

    $role = Role::withoutGlobalScopes()->findOrFail($limitedUser->roles()->firstOrFail()->id);
    DB::beginTransaction();

    try {
        $role->update([
            'permissions' => array_merge($role->permissions ?? [], [
                'viewAny-navmodel' => true,
            ]),
        ]);

        expect($navigationResources())->toContain(NavigationModel::class);
    } finally {
        DB::rollBack();
    }

    Cache::flush();

    expect($navigationResources())->not->toContain(NavigationModel::class);
});

test('an inner role commit followed by an outer rollback does not poison permission or navigation memos', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);
    $limitedUser = createAdmin();
    $this->actingAs($limitedUser);
    $navigationResources = fn () => Aura::navigation()
        ->flatMap(fn (Collection $items): Collection => $items)
        ->pluck('resource');
    $role = Role::withoutGlobalScopes()->findOrFail($limitedUser->roles()->firstOrFail()->id);
    $connection = $role->getConnection();
    $baselineLevel = $connection->transactionLevel();

    expect($limitedUser->hasPermissionTo('viewAny', new NavigationModel))->toBeFalse()
        ->and($navigationResources())->not->toContain(NavigationModel::class);

    $connection->beginTransaction();
    $connection->beginTransaction();

    try {
        $role->update([
            'permissions' => array_replace($role->permissions ?? [], [
                'viewAny-navmodel' => true,
            ]),
        ]);

        expect($limitedUser->hasPermissionTo('viewAny', new NavigationModel))->toBeTrue()
            ->and($navigationResources())->toContain(NavigationModel::class);

        $connection->commit();

        expect($limitedUser->hasPermissionTo('viewAny', new NavigationModel))->toBeTrue();
    } finally {
        while ($connection->transactionLevel() > $baselineLevel) {
            $connection->rollBack();
        }
    }

    Cache::flush();

    expect($limitedUser->hasPermissionTo('viewAny', new NavigationModel))->toBeFalse()
        ->and($navigationResources())->not->toContain(NavigationModel::class);
});

test('membership role changes invalidate warmed navigation', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);
    $limitedUser = createAdmin();
    $roleAttributes = [
        'name' => 'Navigation Viewer',
        'slug' => 'navigation-viewer',
        'permissions' => ['viewAny-navmodel' => true],
        'super_admin' => false,
    ];

    if (config('aura.teams')) {
        $roleAttributes['team_id'] = $limitedUser->current_team_id;
    }

    $allowedRole = Role::create($roleAttributes);
    $this->actingAs($limitedUser);
    $navigationResources = fn () => Aura::navigation()
        ->flatMap(fn (Collection $items): Collection => $items)
        ->pluck('resource');

    expect($navigationResources())->not->toContain(NavigationModel::class);

    if (config('aura.teams')) {
        $limitedUser->roles()->syncWithPivotValues(
            [$allowedRole->id],
            ['team_id' => $limitedUser->current_team_id],
        );
    } else {
        $limitedUser->roles()->sync([$allowedRole->id]);
    }

    expect($navigationResources())->toContain(NavigationModel::class);
});

test('navigation items can be grouped', function () {
    Aura::registerResources([
        GroupedNavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new GroupedNavigationModel)->pluralName())->toBe('NavigationModels');

    expect($nav['Custom Group'])->not->toBeNull();

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Custom Group'])->firstWhere('resource', 'GroupedNavigationModel');

    // Assert that an item was found.
    expect($item)->not->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertSee('Custom Group')
        ->assertSee('NavigationModels');
});

test('navigation items can be dropdown', function () {
    Aura::registerResources([
        DropdownNavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new DropdownNavigationModel)->pluralName())->toBe('NavigationModels');
    expect($nav['Custom Group'])->not->toBeNull();
    expect($nav['Custom Group'][0]['group'])->toBe('Custom Group');
    expect($nav['Custom Group'][0]['dropdown'])->toBe('Custom Dropdown');
    expect($nav['Custom Group'][0]['group'])->toBe('Custom Group');

    $this->get(route('aura.dashboard'))
        ->assertSee('Custom Group')
        ->assertSee('Custom Dropdown')
        ->assertSee('NavigationModels');
});

test('navigation resource structure survives a serialized cache read in a fresh application container', function () {
    $cache = serializedNavigationCacheRepository();
    Cache::swap($cache);
    Aura::registerResources([NavigationModel::class]);

    expect(Aura::navigation())
        ->toBeInstanceOf(Collection::class)
        ->toHaveKey('Resources');

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($this->user);
    Aura::fake();
    Aura::registerResources([NavigationModel::class]);
    Route::get('/navigation-test/navmodel', fn (): string => '')->name('aura.navmodel.index');

    expect(Aura::navigation())
        ->toBeInstanceOf(Collection::class)
        ->toHaveKey('Resources');
});

test('navigation cache changes when the registered resource context changes', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);

    expect(Aura::navigation())->not->toHaveKey('Custom Group');

    Aura::registerResources([GroupedNavigationModel::class]);

    expect(Aura::navigation())
        ->toHaveKey('Custom Group')
        ->and(collect(Aura::navigation()['Custom Group'])->firstWhere('resource', GroupedNavigationModel::class))
        ->not->toBeNull();
});

test('navigation cache changes when navigation hooks change', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);

    expect(Aura::navigation())->not->toHaveKey('Custom Group');

    NavigationRegistry::add([customNonResourceNavigationItem('CustomPage')]);

    expect(Aura::navigation())
        ->toHaveKey('Custom Group')
        ->and(collect(Aura::navigation()['Custom Group'])->firstWhere('type', 'CustomPage'))
        ->not->toBeNull();
});

test('different opaque hooks in fresh managers never share navigation cache entries', function () {
    $cache = serializedNavigationCacheRepository();
    Cache::swap($cache);

    $firstManager = new HookManager;
    app()->instance('hook_manager', $firstManager);
    $firstManager->addHook('navigation', function (Collection $navigation): Collection {
        return $navigation->push(customNonResourceNavigationItem('FirstCallbackPage'));
    });

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('FirstCallbackPage');

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($this->user);
    Aura::fake();

    $secondManager = new HookManager;
    app()->instance('hook_manager', $secondManager);
    $secondManager->addHook('navigation', function (Collection $navigation): Collection {
        return $navigation->push(customNonResourceNavigationItem('SecondCallbackPage'));
    });

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('SecondCallbackPage')
        ->not->toContain('FirstCallbackPage');
});

test('hook cache fingerprints are stable only for deterministic callables', function () {
    $firstManager = new HookManager;
    $firstManager->addHook('stable', 'trim');

    $secondManager = new HookManager;
    $secondManager->addHook('stable', 'trim');

    $differentManager = new HookManager;
    $differentManager->addHook('stable', 'strtolower');

    $opaqueManager = new HookManager;
    $opaqueManager->addHook('stable', fn ($value) => $value);

    expect($firstManager->cacheFingerprint('stable'))
        ->toBe($secondManager->cacheFingerprint('stable'))
        ->not->toBe($differentManager->cacheFingerprint('stable'))
        ->and($opaqueManager->cacheFingerprint('stable'))->toBeNull();
});

test('navigation registration fingerprints are stable across fresh managers', function () {
    $item = customNonResourceNavigationItem('StablePage');

    $firstManager = new HookManager;
    app()->instance('hook_manager', $firstManager);
    NavigationRegistry::add([$item]);

    $secondManager = new HookManager;
    app()->instance('hook_manager', $secondManager);
    NavigationRegistry::add([$item]);

    $differentManager = new HookManager;
    app()->instance('hook_manager', $differentManager);
    NavigationRegistry::add([customNonResourceNavigationItem('DifferentPage')]);

    expect($firstManager->cacheFingerprint('navigation'))
        ->toBe($secondManager->cacheFingerprint('navigation'))
        ->not->toBe($differentManager->cacheFingerprint('navigation'));
});

test('nested non-scalar navigation decorations are never serialized', function (Closure $makeUnsafeValue) {
    $store = serializedNavigationArrayStore();
    Cache::swap(new Repository($store));
    Aura::registerResources([NavigationModel::class]);

    $unsafeValue = $makeUnsafeValue();
    NavigationRegistry::add([
        customNonResourceNavigationItem('UnsafePage', 'Unsafe Group', [
            'metadata' => ['unsafe' => $unsafeValue],
        ]),
    ]);

    $readUnsafeValue = function () {
        $item = collect(Aura::navigation()['Unsafe Group'])->firstWhere('type', 'UnsafePage');

        return $item['metadata']['unsafe'];
    };

    expect(get_debug_type($readUnsafeValue()))->toBe(get_debug_type($unsafeValue))
        ->and(get_debug_type($readUnsafeValue()))->toBe(get_debug_type($unsafeValue));

    $cachedNavigationValues = collect(navigationArrayStoreValues($store))
        ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'aura.cache.value.'));

    expect($cachedNavigationValues)->not->toBeEmpty();

    foreach ($cachedNavigationValues as $value) {
        expect(VersionedCache::isSafe($value))->toBeTrue();
    }

    if (is_resource($unsafeValue)) {
        fclose($unsafeValue);
    }
})->with([
    'closure' => fn () => fn (): string => 'unsafe',
    'resource' => fn () => fopen('php://memory', 'r'),
    'model' => fn () => new NavigationModel,
]);

test('navigation retries when a deterministic hook races its first cache write', function () {
    $store = serializedNavigationArrayStore(InterleavingNavigationArrayStore::class);
    Cache::swap(new Repository($store));
    Aura::registerResources([NavigationModel::class]);

    $store->beforeNextNavigationPut(function (): void {
        NavigationRegistry::add([customNonResourceNavigationItem('RacingPage', 'Racing Group')]);
    });

    expect(collect(Aura::navigation()['Racing Group'])->pluck('type'))
        ->toContain('RacingPage')
        ->and(collect(Aura::navigation()['Racing Group'])->pluck('type'))
        ->toContain('RacingPage');
});

test('navigation accepts a legitimate one-time hook mutation', function () {
    $hookManager = app('hook_manager');
    $mutated = false;
    $invocations = 0;

    $hookManager->addHook('navigation', function (Collection $navigation) use ($hookManager, &$mutated, &$invocations): Collection {
        $invocations++;

        if (! $mutated) {
            $mutated = true;
            $hookManager->addHook(
                'navigation',
                fn (Collection $items): Collection => $items->push(customNonResourceNavigationItem('LatePage')),
                'navigation.late-page.v1',
            );
        }

        return $navigation;
    }, 'navigation.one-time-mutation.v1');

    expect(collect(Aura::navigation()['Custom Group'])->pluck('type'))
        ->toContain('LatePage')
        ->and($invocations)->toBe(2);
});

test('navigation fails closed after bounded continuous hook mutations', function () {
    $hookManager = app('hook_manager');
    $invocations = 0;

    $hookManager->addHook('navigation', function (Collection $navigation) use ($hookManager, &$invocations): Collection {
        $invocations++;

        if ($invocations > 10) {
            throw new LogicException('Unbounded navigation retry test guard reached.');
        }

        $hookManager->addHook(
            'navigation',
            fn (Collection $items): Collection => $items,
            'navigation.continuous-mutation.'.$invocations,
        );

        return $navigation;
    }, 'navigation.continuous-mutation.v1');

    expect(fn () => Aura::navigation())
        ->toThrow(RuntimeException::class, 'Unable to stabilize navigation while hooks are changing.')
        ->and($invocations)->toBe(3);
});

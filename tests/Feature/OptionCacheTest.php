<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\VersionedCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function serializedOptionCacheRepository(): Repository
{
    return new Repository(new ArrayStore(serializesValues: true, serializableClasses: false));
}

test('template catalog survives a serialized cache read in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);
    app(Filesystem::class)->ensureDirectoryExists(app_path('Aura/Templates'));

    expect(Aura::templates())->toBeInstanceOf(Collection::class);

    $this->refreshApplication();
    Cache::swap($cache);

    expect(Aura::templates())->toBeInstanceOf(Collection::class);
});

test('versioned cache degrades to an uncached read when generations cannot persist', function () {
    Cache::swap(new Repository(new NullStore));
    $resolutions = 0;

    $value = VersionedCache::remember(
        'null-store',
        'value',
        60,
        function () use (&$resolutions): array {
            $resolutions++;

            return ['value' => 'fresh'];
        },
    );

    expect($value)->toBe(['value' => 'fresh'])
        ->and($resolutions)->toBe(1);
});

class InterleavingOptionArrayStore extends ArrayStore
{
    private ?Closure $beforeValuePut = null;

    private bool $interleaved = false;

    private ?Closure $keyMatcher = null;

    public function beforeNextMatchingPut(Closure $keyMatcher, Closure $callback): void
    {
        $this->beforeValuePut = $callback;
        $this->keyMatcher = $keyMatcher;
        $this->interleaved = false;
    }

    public function beforeNextValuePut(Closure $callback): void
    {
        $this->beforeValuePut = $callback;
        $this->keyMatcher = fn (string $key): bool => str_starts_with($key, 'aura.option.')
            || str_starts_with($key, 'aura.cache.value.');
        $this->interleaved = false;
    }

    public function put($key, $value, $seconds)
    {
        if (! $this->interleaved
            && $this->beforeValuePut
            && $this->keyMatcher
            && ($this->keyMatcher)($key)) {
            $this->interleaved = true;
            ($this->beforeValuePut)();
        }

        return parent::put($key, $value, $seconds);
    }
}

class LengthLimitedArrayStore extends ArrayStore
{
    public function forget($key)
    {
        $this->assertValidKey($key);

        return parent::forget($key);
    }

    public function get($key)
    {
        $this->assertValidKey($key);

        return parent::get($key);
    }

    public function put($key, $value, $seconds)
    {
        $this->assertValidKey($key);

        return parent::put($key, $value, $seconds);
    }

    private function assertValidKey(string $key): void
    {
        if (strlen($key) > 250) {
            throw new RuntimeException('Cache key exceeds backend limit.');
        }
    }
}

test('user option cache retries when a write races its first read', function () {
    $store = new InterleavingOptionArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));

    $user = createSuperAdmin();
    $user->updateOption('race', ['version' => 1]);

    $store->beforeNextValuePut(fn () => $user->updateOption('race', ['version' => 2]));

    expect($user->getOption('race'))->toBe(['version' => 2])
        ->and($user->getOption('race'))->toBe(['version' => 2]);
});

test('team option cache retries when a write races its first read', function () {
    $store = new InterleavingOptionArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));

    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->updateOption('race', ['version' => 1]);

    $store->beforeNextValuePut(fn () => $team->updateOption('race', ['version' => 2]));

    expect($team->getOption('race'))->toBe(['version' => 2])
        ->and($team->getOption('race'))->toBe(['version' => 2]);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('global option cache retries when a write races its first read', function () {
    $store = new InterleavingOptionArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));

    Aura::updateOption('race', ['version' => 1]);

    $store->beforeNextValuePut(fn () => Aura::updateOption('race', ['version' => 2]));

    expect(Aura::getOption('race'))->toBe(['version' => 2])
        ->and(Aura::getOption('race'))->toBe(['version' => 2]);
})->skip(fn () => config('aura.teams'), 'Global option context requires teams-off mode.');

test('long option names use backend-safe fixed-length cache keys', function () {
    $store = new LengthLimitedArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));

    $user = createSuperAdmin();
    $option = str_repeat('long-option-', 20);
    $user->updateOption($option, ['safe' => true]);

    expect($user->getOption($option))->toBe(['safe' => true]);

    foreach (array_keys($store->all(false)) as $key) {
        expect(strlen($key))->toBeLessThanOrEqual(250);
    }
});

test('regular user team cache retries when team creation races its first read', function () {
    $store = new InterleavingOptionArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));

    $user = createSuperAdmin();
    $newTeam = null;
    $legacyKey = 'user.'.$user->id.'.teams';

    $store->beforeNextMatchingPut(
        fn (string $key): bool => $key === $legacyKey || str_starts_with($key, 'aura.cache.value.'),
        function () use (&$newTeam): void {
            $newTeam = Team::create(['name' => 'Created during team-list read']);
        },
    );

    expect($user->getTeams()->pluck('id'))->toContain($newTeam->id)
        ->and($user->getTeams()->pluck('id'))->toContain($newTeam->id);
})->skip(fn () => ! config('aura.teams'), 'Team list context requires teams enabled.');

test('global admin team cache retries when team creation races its first read', function () {
    $store = new InterleavingOptionArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));

    $globalAdmin = createGlobalAdmin();
    $this->actingAs($globalAdmin);
    $newTeam = null;

    $store->beforeNextMatchingPut(
        fn (string $key): bool => $key === User::GLOBAL_ADMIN_TEAMS_CACHE_KEY
            || str_starts_with($key, 'aura.cache.value.'),
        function () use (&$newTeam): void {
            $newTeam = Team::create(['name' => 'Created during global team-list read']);
        },
    );

    expect($globalAdmin->getTeams()->pluck('id'))->toContain($newTeam->id)
        ->and($globalAdmin->getTeams()->pluck('id'))->toContain($newTeam->id);
})->skip(fn () => ! config('aura.teams'), 'Team list context requires teams enabled.');

test('Aura option reads preserve stored falsey values', function (mixed $value) {
    Cache::swap(serializedOptionCacheRepository());

    if (config('aura.teams')) {
        createSuperAdmin();
    }

    Aura::updateOption('falsey', $value);

    expect(Aura::getOption('falsey'))->toBe($value);
})->with([
    'false' => false,
    'zero' => 0,
    'empty string' => '',
    'null' => null,
]);

test('Aura option reads distinguish a missing row from a stored null', function () {
    Cache::swap(serializedOptionCacheRepository());

    if (config('aura.teams')) {
        createSuperAdmin();
    }

    expect(Aura::getOption('missing'))->toBe([]);

    Aura::updateOption('missing', null);

    expect(Aura::getOption('missing'))->toBeNull();
});

test('exact option cache envelopes retain the found bit for missing and null values', function () {
    $store = new ArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $user = createSuperAdmin();

    expect($user->getOption('missing'))->toBeNull();

    $user->updateOption('stored-null', null);
    expect($user->getOption('stored-null'))->toBeNull();

    $values = collect($store->all())->pluck('value');

    expect($values->contains(fn ($value): bool => $value === ['found' => false, 'value' => null]))->toBeTrue()
        ->and($values->contains(fn ($value): bool => $value === ['found' => true, 'value' => null]))->toBeTrue();
});

test('specialized user options distinguish defaults from a stored null', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();

    expect($user->getOptionBookmarks())->toBe([])
        ->and($user->getOptionColumns('Contact'))->toBe([])
        ->and($user->getOptionSidebar())->toBe([])
        ->and($user->getOptionSidebarToggled())->toBeTrue();

    $user->updateOption('bookmarks', null);
    $user->updateOption('columns.Contact', null);
    $user->updateOption('sidebar', null);
    $user->updateOption('sidebarToggled', null);

    expect($user->getOptionBookmarks())->toBeNull()
        ->and($user->getOptionColumns('Contact'))->toBeNull()
        ->and($user->getOptionSidebar())->toBeNull()
        ->and($user->getOptionSidebarToggled())->toBeNull();
});

test('wildcard option reads preserve every stored falsey value', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();

    $user->updateOption('falsey.false', false);
    $user->updateOption('falsey.zero', 0);
    $user->updateOption('falsey.empty', '');
    $user->updateOption('falsey.null', null);

    expect($user->getOption('falsey.*')->sortKeys()->all())->toBe([
        'empty' => '',
        'false' => false,
        'null' => null,
        'zero' => 0,
    ]);
});

test('regular user teams survive a serialized cache read in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();

    expect($user->getTeams())
        ->toBeInstanceOf(EloquentCollection::class)
        ->each->toBeInstanceOf(Team::class);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    expect($user->getTeams())
        ->toBeInstanceOf(EloquentCollection::class)
        ->each->toBeInstanceOf(Team::class)
        ->and($queries)->toBeEmpty();
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('global admin teams survive a serialized cache read in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $globalAdmin = createGlobalAdmin();
    $this->actingAs($globalAdmin);
    Team::factory()->createQuietly();

    expect($globalAdmin->getTeams())
        ->toBeInstanceOf(EloquentCollection::class)
        ->each->toBeInstanceOf(Team::class);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($globalAdmin);
    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    expect($globalAdmin->getTeams())
        ->toBeInstanceOf(EloquentCollection::class)
        ->each->toBeInstanceOf(Team::class)
        ->and($queries)->toBeEmpty();
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('user option survives a serialized cache read in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $user->updateOption('recent.records', ['contact:1']);

    expect($user->getOption('recent.records'))->toBe(['contact:1']);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($user->getOption('recent.records'))->toBe(['contact:1']);
});

test('wildcard user options survive serialized cache reads as a collection', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $user->updateOption('Contact.filters.mine', ['owner' => 'me']);
    $user->updateOption('Contact.filters.open', ['status' => 'open']);

    expect($user->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);

    expect($user->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);
});

test('specialized user preference getters survive serialized cache reads', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $user->updateOption('bookmarks', [['name' => 'Contacts', 'url' => '/contacts']]);
    $user->updateOption('columns.Contact', ['name' => true, 'owner' => false]);
    $user->updateOption('sidebar', ['Resources']);
    $user->updateOption('sidebarToggled', false);

    $readPreferences = fn () => [
        'bookmarks' => $user->getOptionBookmarks(),
        'columns' => $user->getOptionColumns('Contact'),
        'sidebar' => $user->getOptionSidebar(),
        'sidebarToggled' => $user->getOptionSidebarToggled(),
    ];

    $expected = [
        'bookmarks' => [['name' => 'Contacts', 'url' => '/contacts']],
        'columns' => ['name' => true, 'owner' => false],
        'sidebar' => ['Resources'],
        'sidebarToggled' => false,
    ];

    expect($readPreferences())->toBe($expected);
    expect($readPreferences())->toBe($expected);
});

test('team option survives a serialized cache read in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->updateOption('settings', ['theme' => 'dark']);

    expect($team->getOption('settings'))->toBe(['theme' => 'dark']);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($team->getOption('settings'))->toBe(['theme' => 'dark']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('updating a team option invalidates Aura option reads', function () {
    Cache::swap(serializedOptionCacheRepository());
    createSuperAdmin();

    Aura::updateOption('settings', ['theme' => 'dark']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'dark']);

    Aura::updateOption('settings', ['theme' => 'light']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'light']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('updating a global option invalidates Aura option reads', function () {
    Cache::swap(serializedOptionCacheRepository());

    Aura::updateOption('settings', ['theme' => 'dark']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'dark']);

    Aura::updateOption('settings', ['theme' => 'light']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'light']);
})->skip(fn () => config('aura.teams'), 'Global option context requires teams-off mode.');

test('user options do not leak between users', function () {
    Cache::swap(serializedOptionCacheRepository());

    $firstUser = createSuperAdmin();
    $firstUser->updateOption('columns.Contact', ['name']);

    $secondUser = config('aura.teams')
        ? soleMemberOf($firstUser->currentTeam)
        : createSuperAdminWithoutTeam();
    $this->actingAs($secondUser);
    $secondUser->updateOption('columns.Contact', ['email']);

    expect($secondUser->getOption('columns.Contact'))->toBe(['email']);

    $this->actingAs($firstUser);
    expect($firstUser->getOption('columns.Contact'))->toBe(['name']);
});

test('user options do not leak between teams', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $firstTeam = $user->currentTeam;
    $user->updateOption('columns.Contact', ['name']);
    expect($user->getOption('columns.Contact'))->toBe(['name']);

    $secondTeam = Team::factory()->create();
    expect($user->current_team_id)->toBe($secondTeam->id);

    $user->updateOption('columns.Contact', ['email']);
    expect($user->getOption('columns.Contact'))->toBe(['email']);

    expect($user->switchTeam($firstTeam))->toBeTrue();
    expect($user->getOption('columns.Contact'))->toBe(['name']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('user option changes invalidate cached wildcard reads', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $user->updateOption('Contact.filters.mine', ['owner' => 'me']);
    $user->updateOption('Contact.filters.open', ['status' => 'open']);

    expect($user->getOption('Contact.filters.*')->all())->toBe([
        'mine' => ['owner' => 'me'],
        'open' => ['status' => 'open'],
    ]);

    $user->updateOption('Contact.filters.open', ['status' => 'closed']);
    expect($user->getOption('Contact.filters.*')->all())->toBe([
        'mine' => ['owner' => 'me'],
        'open' => ['status' => 'closed'],
    ]);

    $user->deleteOption('Contact.filters.mine');
    expect($user->getOption('Contact.filters.*')->all())->toBe([
        'open' => ['status' => 'closed'],
    ]);
});

test('wildcard team options survive serialized cache reads in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->updateOption('Contact.filters.mine', ['owner' => 'me']);
    $team->updateOption('Contact.filters.open', ['status' => 'open']);

    expect($team->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($team->getOption('Contact.filters.*'))
        ->toBeInstanceOf(Collection::class)
        ->all()->toBe([
            'mine' => ['owner' => 'me'],
            'open' => ['status' => 'open'],
        ]);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('team option reads use the team instance context', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $firstTeam = $user->currentTeam;
    $firstTeam->updateOption('settings', ['theme' => 'red']);

    $secondTeam = Team::factory()->create();
    $secondTeam->updateOption('settings', ['theme' => 'blue']);

    $firstTeam->clearCachedOption('settings');

    expect($firstTeam->getOption('settings'))->toBe(['theme' => 'red'])
        ->and($secondTeam->getOption('settings'))->toBe(['theme' => 'blue']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('user option reads use the user and team instance context', function () {
    Cache::swap(serializedOptionCacheRepository());

    $firstUser = createSuperAdmin();
    $firstUser->updateOption('columns.Contact', ['name']);

    $secondTeam = Team::factory()->createQuietly(['user_id' => $firstUser->id]);
    $secondUser = soleMemberOf($secondTeam);
    $this->actingAs($secondUser);

    $firstUser->clearCachedOption('columns.Contact');

    expect($firstUser->getOption('columns.Contact'))->toBe(['name']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

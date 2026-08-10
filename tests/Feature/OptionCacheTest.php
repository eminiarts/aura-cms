<?php

use Aura\Base\Exceptions\OptionOwnerIdentityException;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\VersionedCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

class AdversarialOptionUser extends User
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'adversarial_option_users';
}

class CollidingOptionUser extends AdversarialOptionUser
{
    protected function optionNamePrefix(): string
    {
        return 'u00000000000000';
    }
}

function serializedOptionCacheRepository(): Repository
{
    $constructor = new ReflectionMethod(ArrayStore::class, '__construct');

    if ($constructor->getNumberOfParameters() === 1) {
        return new Repository(new ArrayStore(serializesValues: true));
    }

    return new Repository(new ArrayStore(serializesValues: true, serializableClasses: false));
}

/**
 * @return array{SQLiteConnection, SQLiteConnection}
 */
function overlappingCacheConnections(string $first, string $second): array
{
    foreach ([$first, $second] as $name) {
        config()->set('database.connections.'.$name, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    $firstConnection = DB::connection($first);
    $secondConnection = DB::connection($second);
    $transactions = new DatabaseTransactionsManager;
    app()->instance('db.transactions', $transactions);
    $firstConnection->setTransactionManager($transactions);
    $secondConnection->setTransactionManager($transactions);

    return [$firstConnection, $secondConnection];
}

/**
 * @return array{CollidingOptionUser, CollidingOptionUser}
 */
function collidingOptionUsers(): array
{
    $teamId = config('aura.teams') ? createSuperAdmin()->current_team_id : null;

    Schema::create('adversarial_option_users', function (Blueprint $table): void {
        $table->string('id')->primary();
        $table->unsignedBigInteger('current_team_id')->nullable();
        $table->timestamps();
    });

    config()->set('aura.resources.user', CollidingOptionUser::class);

    foreach (['tenant-token-alpha.9f3a', 'tenant-token-beta.4c8b'] as $userId) {
        DB::table('adversarial_option_users')->insert([
            'id' => $userId,
            'current_team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return [
        CollidingOptionUser::withoutGlobalScopes()->findOrFail('tenant-token-alpha.9f3a'),
        CollidingOptionUser::withoutGlobalScopes()->findOrFail('tenant-token-beta.4c8b'),
    ];
}

test('role team and user cache generations invalidate together after writes', function () {
    $user = createSuperAdmin();
    $this->actingAs($user);
    $team = $user->currentTeam;

    $user->updateOption('core05.integration', ['version' => 1]);
    $team->updateOption('core05.integration', ['version' => 1]);

    expect($user->getOption('core05.integration'))->toBe(['version' => 1])
        ->and($team->getOption('core05.integration'))->toBe(['version' => 1]);

    $user->updateOption('core05.integration', ['version' => 2]);
    $team->updateOption('core05.integration', ['version' => 2]);

    expect($user->getOption('core05.integration'))->toBe(['version' => 2])
        ->and($team->getOption('core05.integration'))->toBe(['version' => 2]);

    $globalAdmin = $user->cachedRoles()->firstWhere('slug', 'admin');
    $catalogVersion = Role::catalogVersion();
    $shadow = Role::withoutGlobalScopes()->create([
        'name' => 'Team admin shadow',
        'slug' => 'admin',
        'description' => 'Integration cache canary',
        'super_admin' => false,
        'permissions' => [],
        'team_id' => $team->getKey(),
    ]);

    expect(Role::catalogVersion())->toBeGreaterThan($catalogVersion)
        ->and($user->cachedRoles()->firstWhere('slug', 'admin')->is($shadow))->toBeTrue();

    $shadow->delete();

    expect($user->cachedRoles()->firstWhere('slug', 'admin')->is($globalAdmin))->toBeTrue();
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

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

test('canonical cache identities preserve typed segment boundaries', function () {
    $identities = [
        VersionedCache::identity('segments'),
        VersionedCache::identity('segments', ''),
        VersionedCache::identity('segments', 1),
        VersionedCache::identity('segments', '1'),
        VersionedCache::identity('segments', 'customer', 'eu.secret'),
        VersionedCache::identity('segments', 'customer.eu', 'secret'),
        VersionedCache::identity('segments', 'Grüezi 👋'),
        VersionedCache::identity('segments', "binary-like\0.\x01\x7f"),
        VersionedCache::identity('segments', str_repeat('long-segment-', 1000)),
        VersionedCache::identity('segments', 'a', 'bc'),
        VersionedCache::identity('segments', 'ab', 'c'),
    ];

    expect(array_unique($identities))->toHaveCount(count($identities));

    foreach ($identities as $identity) {
        expect($identity)->toMatch('/\A[a-f0-9]{64}\z/');
    }
});

test('user option prefixes preserve typed ownership within the legacy name limit', function () {
    $prefixes = [
        User::optionNamePrefixFor(1),
        User::optionNamePrefixFor('1'),
        User::optionNamePrefixFor('customer'),
        User::optionNamePrefixFor('customer.eu'),
    ];

    expect(array_unique($prefixes))->toHaveCount(count($prefixes));

    foreach ($prefixes as $prefix) {
        expect($prefix)->toMatch('/\Au[0123456789abcdefghjkmnpqrstvwxyz]{14}\z/')
            ->and(strlen($prefix))->toBe(15)
            ->and(strlen($prefix.str_repeat('x', 240)))->toBe(255);
    }
});

test('a legacy-limit user option round trips within varchar 255 storage', function () {
    $user = createSuperAdmin();
    $option = str_repeat('x', 240);

    $user->updateOption($option, ['stored' => true]);

    $record = Option::withoutGlobalScopes()->sole();
    $physicalName = $record->getRawOriginal('name');

    expect($user->getOption($option))->toBe(['stored' => true])
        ->and(strlen($physicalName))->toBeLessThanOrEqual(255)
        ->and($physicalName)->toEndWith($option)
        ->and($record->getRawOriginal('owner_identity'))->toBe(
            VersionedCache::identity('option.user.owner', $user->getKey()),
        );
});

test('colliding compact owner identities fail closed without claiming or mutating rows', function (string $operation) {
    [$firstUser, $secondUser] = collidingOptionUsers();
    $firstUser->updateOption('private.setting', ['owner' => 'first']);

    expect($firstUser->getOption('private.setting'))->toBe(['owner' => 'first']);

    $attempt = match ($operation) {
        'read' => fn () => $secondUser->getOption('private.setting'),
        'wildcard read' => fn () => $secondUser->getOption('private.*'),
        'update' => fn () => $secondUser->updateOption('private.setting', ['owner' => 'second']),
        'delete' => fn () => $secondUser->deleteOption('private.setting'),
    };

    expect($attempt)->toThrow(OptionOwnerIdentityException::class);

    $record = Option::withoutGlobalScopes()->withTrashed()->sole();

    expect($record->trashed())->toBeFalse()
        ->and($record->getAttributeValue('value'))->toBe(['owner' => 'first'])
        ->and($record->getRawOriginal('owner_identity'))->toBe(
            VersionedCache::identity('option.user.owner', $firstUser->getKey()),
        );
})->with(['read', 'wildcard read', 'update', 'delete']);

test('a colliding owner creation race becomes an actionable ownership failure', function () {
    [$firstUser, $secondUser] = collidingOptionUsers();
    $insertedCompetingRow = false;

    Option::creating(function (Option $option) use ($firstUser, &$insertedCompetingRow): void {
        if ($insertedCompetingRow) {
            return;
        }

        $insertedCompetingRow = true;
        $attributes = [
            'name' => $option->getAttribute('name'),
            'owner_identity' => VersionedCache::identity('option.user.owner', $firstUser->getKey()),
            'value' => json_encode(['owner' => 'first']),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (config('aura.teams')) {
            $attributes['team_id'] = $option->getAttribute('team_id');
        }

        DB::table($option->getTable())->insert($attributes);
    });

    expect(fn () => $secondUser->updateOption('private.race', ['owner' => 'second']))
        ->toThrow(OptionOwnerIdentityException::class)
        ->and($insertedCompetingRow)->toBeTrue()
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});

test('a same-owner creation race converges on one verified row', function () {
    [$user] = collidingOptionUsers();
    $insertedCompetingRow = false;

    Option::creating(function (Option $option) use ($user, &$insertedCompetingRow): void {
        if ($insertedCompetingRow) {
            return;
        }

        $insertedCompetingRow = true;
        $attributes = [
            'name' => $option->getAttribute('name'),
            'owner_identity' => VersionedCache::identity('option.user.owner', $user->getKey()),
            'value' => json_encode(['version' => 1]),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (config('aura.teams')) {
            $attributes['team_id'] = $option->getAttribute('team_id');
        }

        DB::table($option->getTable())->insert($attributes);
    });

    $user->updateOption('private.same-owner-race', ['version' => 2]);

    expect($insertedCompetingRow)->toBeTrue()
        ->and(Option::withoutGlobalScopes()->count())->toBe(1)
        ->and($user->getOption('private.same-owner-race'))->toBe(['version' => 2]);
});

test('ambiguous pre-verifier canonical rows stay unclaimed without leaking owner identifiers', function (string $operation) {
    [$firstUser] = collidingOptionUsers();
    $attributes = [
        'name' => 'u00000000000000private.ambiguous',
        'owner_identity' => null,
        'value' => ['preserved' => true],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $firstUser->current_team_id;
    }

    $record = Option::withoutGlobalScopes()->create($attributes);

    $attempt = match ($operation) {
        'read' => fn () => $firstUser->getOption('private.ambiguous'),
        'update' => fn () => $firstUser->updateOption('private.ambiguous', ['preserved' => false]),
        'delete' => fn () => $firstUser->deleteOption('private.ambiguous'),
    };

    try {
        $attempt();
        $this->fail('Expected an unverifiable owner identity to fail closed.');
    } catch (OptionOwnerIdentityException $exception) {
        expect($exception->getMessage())->toContain('option row ['.$record->id.']')
            ->not->toContain((string) $firstUser->getKey())
            ->not->toContain('private.ambiguous');
    }

    expect($record->fresh()->getRawOriginal('owner_identity'))->toBeNull()
        ->and($record->fresh()->getAttributeValue('value'))->toBe(['preserved' => true]);
})->with(['read', 'update', 'delete']);

test('option physical identities are unique within their database scope', function () {
    $attributes = [
        'name' => 'unique-option-identity',
        'value' => ['version' => 1],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = createSuperAdmin()->current_team_id;
    }

    Option::withoutGlobalScopes()->create($attributes);

    expect(fn () => Option::withoutGlobalScopes()->create([
        ...$attributes,
        'value' => ['version' => 2],
    ]))->toThrow(QueryException::class);
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

class GenerationRejectingArrayStore extends ArrayStore
{
    public function put($key, $value, $seconds)
    {
        if (str_starts_with($key, 'aura.cache.generation.')) {
            return false;
        }

        return parent::put($key, $value, $seconds);
    }
}

class GenerationReadFailingArrayStore extends ArrayStore
{
    public function get($key)
    {
        if (str_starts_with($key, 'aura.cache.generation.')) {
            throw new RuntimeException('Generation reads are unavailable.');
        }

        return parent::get($key);
    }
}

class GenerationBumpFailingArrayStore extends ArrayStore
{
    private bool $rejectGenerationWrites = false;

    public function put($key, $value, $seconds)
    {
        if ($this->rejectGenerationWrites && str_starts_with($key, 'aura.cache.generation.')) {
            return false;
        }

        return parent::put($key, $value, $seconds);
    }

    public function rejectGenerationWrites(): void
    {
        $this->rejectGenerationWrites = true;
    }
}

class GenerationWriteMisreportingArrayStore extends ArrayStore
{
    private bool $misreportGenerationWrites = false;

    public function misreportGenerationWrites(): void
    {
        $this->misreportGenerationWrites = true;
    }

    public function put($key, $value, $seconds)
    {
        if ($this->misreportGenerationWrites && str_starts_with($key, 'aura.cache.generation.')) {
            return true;
        }

        return parent::put($key, $value, $seconds);
    }
}

class ConcurrentGenerationReplacementStore extends ArrayStore
{
    private bool $replaceGeneration = false;

    public function forget($key)
    {
        $forgotten = parent::forget($key);

        if ($this->replaceGeneration && str_starts_with($key, 'aura.cache.generation.')) {
            $this->replaceGeneration = false;
            parent::put($key, 'concurrent-generation', 3600);
        }

        return $forgotten;
    }

    public function put($key, $value, $seconds)
    {
        if ($this->replaceGeneration && str_starts_with($key, 'aura.cache.generation.')) {
            return false;
        }

        return parent::put($key, $value, $seconds);
    }

    public function replaceAfterFailedBump(): void
    {
        $this->replaceGeneration = true;
    }
}

class IncrementRejectingArrayStore extends ArrayStore
{
    public function increment($key, $value = 1)
    {
        return false;
    }
}

class ChurningGenerationArrayStore extends ArrayStore
{
    private int $remainingBumps = 0;

    public function churnForValueWrites(int $writes): void
    {
        $this->remainingBumps = $writes;
    }

    public function put($key, $value, $seconds)
    {
        $written = parent::put($key, $value, $seconds);

        if ($this->remainingBumps > 0 && str_starts_with($key, 'aura.cache.value.')) {
            $this->remainingBumps--;
            VersionedCache::bump('bounded-churn');
        }

        return $written;
    }
}

class FinalGenerationReadFailingArrayStore extends ArrayStore
{
    private int $generationReads = 0;

    public function get($key)
    {
        if (str_starts_with($key, 'aura.cache.generation.')) {
            $this->generationReads++;

            if ($this->generationReads >= 4) {
                throw new RuntimeException('Final generation verification failed.');
            }
        }

        return parent::get($key);
    }
}

test('versioned cache never stores values under a fallback generation when generation persistence fails', function () {
    $store = new GenerationRejectingArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $source = ['version' => 1];
    $resolutions = 0;

    $read = function () use (&$source, &$resolutions): array {
        return VersionedCache::remember('partial-store', 'value', 60, function () use (&$source, &$resolutions): array {
            $resolutions++;

            return $source;
        });
    };

    expect($read())->toBe(['version' => 1]);

    $source = ['version' => 2];
    VersionedCache::bump('partial-store');

    expect($read())->toBe(['version' => 2])
        ->and($resolutions)->toBe(2)
        ->and(array_filter(
            array_keys($store->all(false)),
            fn (string $key): bool => str_starts_with($key, 'aura.cache.value.'),
        ))->toBe([]);
});

test('versioned cache bypasses persistent values when generation reads fail', function () {
    $store = new GenerationReadFailingArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $resolutions = 0;

    $read = function () use (&$resolutions): array {
        return VersionedCache::remember('generation-read-failure', 'value', 60, function () use (&$resolutions): array {
            $resolutions++;

            return ['resolution' => $resolutions];
        });
    };

    expect($read())->toBe(['resolution' => 1])
        ->and($read())->toBe(['resolution' => 2])
        ->and(array_filter(
            array_keys($store->all(false)),
            fn (string $key): bool => str_starts_with($key, 'aura.cache.value.'),
        ))->toBe([]);
});

test('failed generation bumps make the namespace uncached instead of retaining stale values', function () {
    $store = new GenerationBumpFailingArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $source = ['version' => 1];

    $read = function () use (&$source): array {
        return VersionedCache::remember('failed-bump', 'value', 60, fn (): array => $source);
    };

    expect($read())->toBe(['version' => 1]);

    $source = ['version' => 2];
    $store->rejectGenerationWrites();
    VersionedCache::bump('failed-bump');

    expect($read())->toBe(['version' => 2])
        ->and(array_filter(
            array_keys($store->all(false)),
            fn (string $key): bool => str_starts_with($key, 'aura.cache.generation.'),
        ))->toBe([]);
});

test('generation bumps verify persisted writes before retaining cached values', function () {
    $store = new GenerationWriteMisreportingArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $source = ['version' => 1];
    $read = function () use (&$source): array {
        return VersionedCache::remember('misreported-bump', 'value', 60, fn (): array => $source);
    };

    expect($read())->toBe(['version' => 1]);

    $source = ['version' => 2];
    $store->misreportGenerationWrites();
    VersionedCache::bump('misreported-bump');

    expect($read())->toBe(['version' => 2]);
});

test('generation bumps accept a concurrent fresh token after a failed write', function () {
    $store = new ConcurrentGenerationReplacementStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $source = ['version' => 1];
    $read = function () use (&$source): array {
        return VersionedCache::remember(
            'concurrent-generation-replacement',
            'value',
            60,
            fn (): array => $source,
        );
    };

    expect($read())->toBe(['version' => 1]);

    $source = ['version' => 2];
    $store->replaceAfterFailedBump();
    VersionedCache::bump('concurrent-generation-replacement');

    expect($read())->toBe(['version' => 2]);
});

test('versioned cache does not depend on unsupported generation increments', function () {
    $store = new IncrementRejectingArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $source = ['version' => 1];
    $read = function () use (&$source): array {
        return VersionedCache::remember('unsupported-increment', 'value', 60, fn (): array => $source);
    };

    expect($read())->toBe(['version' => 1]);

    $source = ['version' => 2];
    VersionedCache::bump('unsupported-increment');

    expect($read())->toBe(['version' => 2]);
});

test('continuous generation churn falls back to a bounded uncached resolution', function () {
    $store = new ChurningGenerationArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $store->churnForValueWrites(10);
    $resolutions = 0;

    $value = VersionedCache::remember('bounded-churn', 'value', 60, function () use (&$resolutions): array {
        $resolutions++;

        return ['fresh' => true];
    });

    expect($value)->toBe(['fresh' => true])
        ->and($resolutions)->toBe(4);
});

test('a failed final generation check returns the uncached resolution without resolving twice', function () {
    $store = new FinalGenerationReadFailingArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $resolutions = 0;

    $value = VersionedCache::remember('final-generation-failure', 'value', 60, function () use (&$resolutions): array {
        $resolutions++;

        return ['resolution' => $resolutions];
    });

    expect($value)->toBe(['resolution' => 1])
        ->and($resolutions)->toBe(1)
        ->and(array_filter(
            array_keys($store->all(false)),
            fn (string $key): bool => str_starts_with($key, 'aura.cache.value.'),
        ))->toBe([]);
});

test('generation bumps wait for the outer commit while other connections retain the committed snapshot', function () {
    $store = new ArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    config()->set('database.connections.cache_probe', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    $writer = DB::connection();
    $reader = DB::connection('cache_probe');
    $baselineLevel = $writer->transactionLevel();
    $generation = function () use ($store): ?string {
        $item = collect($store->all())->first(
            fn (array $item, string $key): bool => str_starts_with($key, 'aura.cache.generation.')
        );

        return $item['value'] ?? null;
    };

    expect(VersionedCache::remember('commit-race', 'value', 60, fn (): array => ['version' => 1]))
        ->toBe(['version' => 1]);
    $committedGeneration = $generation();

    $writer->beginTransaction();

    try {
        VersionedCache::bump('commit-race', $writer);

        expect($generation())->toBe($committedGeneration)
            ->and(VersionedCache::remember(
                'commit-race',
                'value',
                60,
                fn (): array => ['version' => 2],
                $writer,
            ))->toBe(['version' => 2])
            ->and(VersionedCache::remember(
                'commit-race',
                'value',
                60,
                fn (): array => ['version' => 1],
                $reader,
            ))->toBe(['version' => 1]);

        $writer->beginTransaction();
        VersionedCache::bump('commit-race', $writer);
        $writer->commit();

        expect($generation())->toBe($committedGeneration);

        $writer->commit();
    } finally {
        while ($writer->transactionLevel() > $baselineLevel) {
            $writer->rollBack();
        }
    }

    expect($generation())->not->toBe($committedGeneration)
        ->and(VersionedCache::remember(
            'commit-race',
            'value',
            60,
            fn (): array => ['version' => 2],
            $reader,
        ))->toBe(['version' => 2]);
});

test('a committed connection owns its invalidation while another connection rolls back', function () {
    Cache::swap(serializedOptionCacheRepository());
    [$writerA, $writerB] = overlappingCacheConnections('cache_writer_a', 'cache_writer_b');
    $source = ['version' => 1];
    $read = function () use (&$source): array {
        return VersionedCache::remember(
            'overlapping-connections-a-commit',
            'value',
            60,
            fn (): array => $source,
        );
    };

    expect($read())->toBe(['version' => 1]);

    $writerA->beginTransaction();
    $writerB->beginTransaction();

    try {
        VersionedCache::bump('overlapping-connections-a-commit', $writerA);
        $source = ['version' => 2];
        $writerA->commit();

        expect($read())->toBe(['version' => 2]);

        $writerB->rollBack();

        expect($read())->toBe(['version' => 2]);
    } finally {
        while ($writerA->transactionLevel() > 0) {
            $writerA->rollBack();
        }

        while ($writerB->transactionLevel() > 0) {
            $writerB->rollBack();
        }
    }
});

test('an unrelated commit cannot publish a writers uncommitted generation', function () {
    Cache::swap(serializedOptionCacheRepository());
    [$writerA, $writerB] = overlappingCacheConnections('early_commit_writer_a', 'early_commit_writer_b');
    $committedSource = ['version' => 1];
    $read = function () use (&$committedSource): array {
        return VersionedCache::remember(
            'overlapping-connections-early-commit',
            'value',
            60,
            fn (): array => $committedSource,
        );
    };

    expect($read())->toBe(['version' => 1]);

    $writerA->beginTransaction();
    $writerB->beginTransaction();

    try {
        VersionedCache::bump('overlapping-connections-early-commit', $writerA);
        $writerB->commit();

        expect($read())->toBe(['version' => 1]);

        $committedSource = ['version' => 2];
        $writerA->commit();

        expect($read())->toBe(['version' => 2]);
    } finally {
        while ($writerA->transactionLevel() > 0) {
            $writerA->rollBack();
        }

        while ($writerB->transactionLevel() > 0) {
            $writerB->rollBack();
        }
    }
});

test('connection callback ownership is independent of connection start order', function () {
    Cache::swap(serializedOptionCacheRepository());
    [$writerA, $writerB] = overlappingCacheConnections('reverse_writer_a', 'reverse_writer_b');
    $source = ['version' => 1];
    $read = function () use (&$source): array {
        return VersionedCache::remember(
            'overlapping-connections-reverse-order',
            'value',
            60,
            fn (): array => $source,
        );
    };

    expect($read())->toBe(['version' => 1]);

    $writerB->beginTransaction();
    $writerA->beginTransaction();

    try {
        VersionedCache::bump('overlapping-connections-reverse-order', $writerB);
        $source = ['version' => 2];
        $writerB->commit();

        expect($read())->toBe(['version' => 2]);

        $writerA->rollBack();

        expect($read())->toBe(['version' => 2]);
    } finally {
        while ($writerA->transactionLevel() > 0) {
            $writerA->rollBack();
        }

        while ($writerB->transactionLevel() > 0) {
            $writerB->rollBack();
        }
    }
});

test('a nested rollback discards only its connections invalidation while another transaction is open', function () {
    Cache::swap(serializedOptionCacheRepository());
    [$writerA, $writerB] = overlappingCacheConnections('nested_writer_a', 'nested_writer_b');
    $source = ['version' => 1];
    $read = function () use (&$source): array {
        return VersionedCache::remember(
            'overlapping-connections-nested-rollback',
            'value',
            60,
            fn (): array => $source,
        );
    };

    expect($read())->toBe(['version' => 1]);

    $writerA->beginTransaction();
    $writerB->beginTransaction();
    $writerA->beginTransaction();

    try {
        VersionedCache::bump('overlapping-connections-nested-rollback', $writerA);
        $writerA->rollBack();
        $writerA->commit();
        $writerB->rollBack();
        $source = ['version' => 2];

        expect($read())->toBe(['version' => 1]);
    } finally {
        while ($writerA->transactionLevel() > 0) {
            $writerA->rollBack();
        }

        while ($writerB->transactionLevel() > 0) {
            $writerB->rollBack();
        }
    }
});

test('rolled back nested generation bumps leave the committed generation usable', function () {
    $store = new ArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $connection = DB::connection();
    $baselineLevel = $connection->transactionLevel();
    $generation = function () use ($store): ?string {
        $item = collect($store->all())->first(
            fn (array $item, string $key): bool => str_starts_with($key, 'aura.cache.generation.')
        );

        return $item['value'] ?? null;
    };

    expect(VersionedCache::remember('nested-rollback', 'value', 60, fn (): array => ['version' => 1]))
        ->toBe(['version' => 1]);
    $committedGeneration = $generation();

    $connection->beginTransaction();
    $connection->beginTransaction();

    try {
        VersionedCache::bump('nested-rollback', $connection);
        $connection->rollBack();
        $connection->commit();
    } finally {
        while ($connection->transactionLevel() > $baselineLevel) {
            $connection->rollBack();
        }
    }

    expect($generation())->toBe($committedGeneration)
        ->and(VersionedCache::remember('nested-rollback', 'value', 60, fn (): array => ['version' => 2]))
        ->toBe(['version' => 1]);
});

test('rollback cleanup survives an inner commit with the installed transaction manager', function () {
    config()->set('database.connections.installed_manager_nested_rollback', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $connection = DB::connection('installed_manager_nested_rollback');
    $rollbacks = 0;

    $connection->beginTransaction();
    $connection->beginTransaction();

    try {
        VersionedCache::afterRollback($connection, function () use (&$rollbacks): void {
            $rollbacks++;
        });

        $connection->commit();
        $connection->rollBack();
    } finally {
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
    }

    expect($rollbacks)->toBe(1);
});

test('rollback cleanup survives a committed inner transaction level being reused', function () {
    config()->set('database.connections.reused_nested_transaction_level', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $connection = DB::connection('reused_nested_transaction_level');
    $rollbacks = 0;

    $connection->beginTransaction();
    $connection->beginTransaction();

    try {
        VersionedCache::afterRollback($connection, function () use (&$rollbacks): void {
            $rollbacks++;
        });

        $connection->commit();
        $connection->beginTransaction();
        $connection->rollBack();

        expect($rollbacks)->toBe(0);

        $connection->rollBack();
    } finally {
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
    }

    expect($rollbacks)->toBe(1);
});

test('rollback cleanup is discarded after commit', function () {
    config()->set('database.connections.committed_rollback_cleanup', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $connection = DB::connection('committed_rollback_cleanup');
    $rollbacks = 0;

    $connection->beginTransaction();
    VersionedCache::afterRollback($connection, function () use (&$rollbacks): void {
        $rollbacks++;
    });
    $connection->commit();

    expect($rollbacks)->toBe(0);
});

test('rollback cleanup runs only for the rolled back retry attempt', function () {
    config()->set('database.connections.retried_rollback_cleanup', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $connection = DB::connection('retried_rollback_cleanup');
    $attempts = 0;
    $rollbacks = 0;

    $result = $connection->transaction(
        function (SQLiteConnection $activeConnection) use (&$attempts, &$rollbacks): string {
            $attempts++;

            VersionedCache::afterRollback($activeConnection, function () use (&$rollbacks): void {
                $rollbacks++;
            });

            if ($attempts === 1) {
                throw new RuntimeException('database is locked');
            }

            return 'committed';
        },
        attempts: 2,
    );

    expect($result)->toBe('committed')
        ->and($attempts)->toBe(2)
        ->and($rollbacks)->toBe(1);
});

test('rollback cleanup runs once when an inner transaction rolls back before its outer transaction', function () {
    config()->set('database.connections.nested_rollback_once', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $connection = DB::connection('nested_rollback_once');
    $transactions = new DatabaseTransactionsManager;
    app()->instance('db.transactions', $transactions);
    $connection->setTransactionManager($transactions);
    $rollbacks = 0;

    $connection->beginTransaction();
    $connection->beginTransaction();

    VersionedCache::afterRollback($connection, function () use (&$rollbacks): void {
        $rollbacks++;
    });

    try {
        $connection->rollBack();

        expect($rollbacks)->toBe(1);

        $connection->rollBack();

        expect($rollbacks)->toBe(1);
    } finally {
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
    }
});

test('transactions on unmanaged connections still bypass persistent cache reads', function () {
    Cache::swap(serializedOptionCacheRepository());
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'), 'unmanaged-cache-probe');

    expect(VersionedCache::remember('unmanaged-transaction', 'value', 60, fn (): array => ['version' => 1]))
        ->toBe(['version' => 1]);

    $connection->beginTransaction();

    try {
        expect(VersionedCache::remember(
            'unmanaged-transaction',
            'value',
            60,
            fn (): array => ['version' => 2],
            $connection,
        ))->toBe(['version' => 2]);
    } finally {
        $connection->rollBack();
    }
});

test('invalidations fail closed when an active transaction has no callback manager', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'), 'unmanaged-invalidation-probe');
    $connection->beginTransaction();

    try {
        expect(fn () => VersionedCache::bump('unmanaged-invalidation', $connection))
            ->toThrow(
                RuntimeException::class,
                'Unable to bind cache invalidation to the active database transaction.',
            );
    } finally {
        $connection->rollBack();
    }
});

test('user option cache retries when a write races its first read', function () {
    $store = new InterleavingOptionArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));

    $user = createSuperAdmin();
    $user->updateOption('race', ['version' => 1]);

    $store->beforeNextValuePut(fn () => $user->updateOption('race', ['version' => 2]));

    expect($user->getOption('race'))->toBe(['version' => 2])
        ->and($user->getOption('race'))->toBe(['version' => 2]);
});

test('user option reads inside a rolled back transaction never publish uncommitted values', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('transaction', ['version' => 1]);

    expect($user->getOption('transaction'))->toBe(['version' => 1]);

    DB::beginTransaction();

    try {
        $user->updateOption('transaction', ['version' => 2]);

        expect($user->getOption('transaction'))->toBe(['version' => 2]);
    } finally {
        DB::rollBack();
    }

    expect($user->getOption('transaction'))->toBe(['version' => 1]);
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

test('global option reads inside a rolled back transaction never publish uncommitted values', function () {
    Cache::swap(serializedOptionCacheRepository());
    Aura::updateOption('transaction', ['version' => 1]);

    expect(Aura::getOption('transaction'))->toBe(['version' => 1]);

    DB::beginTransaction();

    try {
        Aura::updateOption('transaction', ['version' => 2]);

        expect(Aura::getOption('transaction'))->toBe(['version' => 2]);
    } finally {
        DB::rollBack();
    }

    expect(Aura::getOption('transaction'))->toBe(['version' => 1]);
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

test('renaming a team invalidates every current member team snapshot', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->update(['name' => 'Before rename']);

    expect($user->getTeams()->firstWhere('id', $team->id)->name)->toBe('Before rename');

    $team->update(['name' => 'After rename']);

    expect($user->getTeams()->firstWhere('id', $team->id)->name)->toBe('After rename');
})->skip(fn () => ! config('aura.teams'), 'Team list context requires teams enabled.');

test('membership pivot lifecycle invalidates team snapshots for direct attach and detach writes', function () {
    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $team = $actor->currentTeam;
    $role = Role::factory()->create(['team_id' => $team->id]);
    $member = User::factory()->create(['current_team_id' => $team->id]);

    expect($member->getTeams())->toHaveCount(0);

    $member->roles()->attach($role->id, ['team_id' => $team->id]);

    expect($member->getTeams()->pluck('id'))->toContain($team->id);

    $member->roles()->wherePivot('team_id', $team->id)->detach($role->id);

    expect($member->getTeams()->pluck('id'))->not->toContain($team->id);
})->skip(fn () => ! config('aura.teams'), 'Team list context requires teams enabled.');

test('role-side user membership writes invalidate permission memos', function () {
    $team = config('aura.teams') ? createSuperAdmin()->currentTeam : null;
    $user = User::factory()->create(
        config('aura.teams') ? ['current_team_id' => $team->id] : [],
    );
    $attributes = [
        'name' => 'Role-side permission',
        'slug' => 'role-side-permission',
        'permissions' => ['core04-role-side' => true],
        'super_admin' => false,
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $user->current_team_id;
    }

    $role = Role::withoutGlobalScopes()->create($attributes);
    $pivot = config('aura.teams') ? ['team_id' => $user->current_team_id] : [];

    expect($user->hasPermission('core04-role-side'))->toBeFalse();

    $role->users()->attach($user->id, $pivot);
    expect($user->hasPermission('core04-role-side'))->toBeTrue();

    $role->users()->detach($user->id);
    expect($user->hasPermission('core04-role-side'))->toBeFalse();

    $role->users()->sync([$user->id => $pivot]);
    expect($user->hasPermission('core04-role-side'))->toBeTrue();

    $role->users()->sync([]);
    expect($user->hasPermission('core04-role-side'))->toBeFalse();
});

test('role-side user membership rollback restores permission memos after an inner commit', function () {
    $team = config('aura.teams') ? createSuperAdmin()->currentTeam : null;
    $user = User::factory()->create(
        config('aura.teams') ? ['current_team_id' => $team->id] : [],
    );
    $attributes = [
        'name' => 'Role-side rollback',
        'slug' => 'role-side-rollback',
        'permissions' => ['core04-role-side-rollback' => true],
        'super_admin' => false,
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $user->current_team_id;
    }

    $role = Role::withoutGlobalScopes()->create($attributes);
    $pivot = config('aura.teams') ? ['team_id' => $user->current_team_id] : [];
    $connection = $role->getConnection();
    $baselineLevel = $connection->transactionLevel();

    expect($user->hasPermission('core04-role-side-rollback'))->toBeFalse();

    $connection->beginTransaction();
    $connection->beginTransaction();

    try {
        $role->users()->attach($user->id, $pivot);

        expect($user->hasPermission('core04-role-side-rollback'))->toBeTrue();

        $connection->commit();
    } finally {
        while ($connection->transactionLevel() > $baselineLevel) {
            $connection->rollBack();
        }
    }

    expect($user->hasPermission('core04-role-side-rollback'))->toBeFalse();
});

test('role-side team membership writes invalidate team snapshots and roll back safely', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Role-to-team Memberships require teams enabled.');
    }

    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $team = $actor->currentTeam;
    $user = User::factory()->create(['current_team_id' => $team->id]);
    $role = Role::withoutGlobalScopes()->create([
        'name' => 'Role-side team snapshot',
        'slug' => 'role-side-team-snapshot',
        'permissions' => [],
        'super_admin' => false,
        'team_id' => $team->id,
    ]);

    expect($user->getTeams())->toHaveCount(0);

    $connection = $role->getConnection();
    $baselineLevel = $connection->transactionLevel();
    $connection->beginTransaction();

    try {
        $role->teams()->attach($team->id, ['user_id' => $user->id]);
        expect($user->getTeams()->pluck('id'))->toContain($team->id);
    } finally {
        while ($connection->transactionLevel() > $baselineLevel) {
            $connection->rollBack();
        }
    }

    expect($user->getTeams()->pluck('id'))->not->toContain($team->id);
});

test('role-side team attach detach and sync invalidate warmed team snapshots', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Role-to-team Memberships require teams enabled.');
    }

    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $team = $actor->currentTeam;
    $user = User::factory()->create(['current_team_id' => $team->id]);
    $role = Role::withoutGlobalScopes()->create([
        'name' => 'Role-side team lifecycle',
        'slug' => 'role-side-team-lifecycle',
        'permissions' => [],
        'super_admin' => false,
        'team_id' => $team->id,
    ]);
    $pivot = ['user_id' => $user->id];

    expect($user->getTeams())->toHaveCount(0);

    $role->teams()->attach($team->id, $pivot);
    expect($user->getTeams()->pluck('id'))->toContain($team->id);

    $role->teams()->detach($team->id);
    expect($user->getTeams()->pluck('id'))->not->toContain($team->id);

    $role->teams()->sync([$team->id => $pivot]);
    expect($user->getTeams()->pluck('id'))->toContain($team->id);

    $role->teams()->sync([]);
    expect($user->getTeams()->pluck('id'))->not->toContain($team->id);
});

test('user-side sync removals invalidate warmed team snapshots across fresh model instances', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('User-to-role Memberships require teams enabled.');
    }

    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $team = $actor->currentTeam;
    $role = Role::factory()->create(['team_id' => $team->id]);
    $member = User::factory()->create(['current_team_id' => $team->id]);

    $member->roles()->attach($role->id, ['team_id' => $team->id]);
    expect($member->getTeams()->pluck('id'))->toContain($team->id);

    Aura::flushState();
    $freshMember = User::withoutGlobalScopes()->findOrFail($member->id);
    $freshMember->roles()->sync([]);

    expect(User::withoutGlobalScopes()->findOrFail($member->id)->getTeams()->pluck('id'))->not->toContain($team->id);
});

test('role-side bulk detach invalidates every affected membership without invalidating unrelated users', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Role-to-user Memberships require teams enabled.');
    }

    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $firstTeam = $actor->currentTeam;
    $secondTeam = Team::factory()->create(['name' => 'Second team']);
    $role = Role::withoutGlobalScopes()->create([
        'name' => 'Bulk membership role',
        'slug' => 'bulk-membership-role',
        'permissions' => [],
        'super_admin' => false,
        'team_id' => $firstTeam->id,
    ]);
    $member = User::factory()->create(['current_team_id' => $firstTeam->id]);
    $unrelatedMember = User::factory()->create(['current_team_id' => $secondTeam->id]);

    $role->users()->attach($member->id, ['team_id' => $firstTeam->id]);
    $role->users()->attach($member->id, ['team_id' => $secondTeam->id]);
    $role->users()->attach($unrelatedMember->id, ['team_id' => $secondTeam->id]);

    expect($member->getTeams()->pluck('id')->all())->toEqualCanonicalizing([$firstTeam->id, $secondTeam->id])
        ->and($unrelatedMember->getTeams()->firstWhere('id', $secondTeam->id)->name)->toBe('Second team');

    $secondTeam->forceFill(['name' => 'Renamed without events'])->saveQuietly();
    $role->users()->detach($member->id);

    expect($member->getTeams())->toHaveCount(0)
        ->and($unrelatedMember->getTeams()->firstWhere('id', $secondTeam->id)->name)->toBe('Second team');
});

test('pivot-constrained detach preserves the same role membership in another team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Pivot-constrained Memberships require teams enabled.');
    }

    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $firstTeam = $actor->currentTeam;
    $secondTeam = Team::factory()->create();
    $role = Role::factory()->create(['team_id' => $firstTeam->id]);
    $member = User::factory()->create(['current_team_id' => $firstTeam->id]);

    $member->roles()->attach($role->id, ['team_id' => $firstTeam->id]);
    $member->roles()->attach($role->id, ['team_id' => $secondTeam->id]);

    expect($member->getTeams()->pluck('id')->all())->toEqualCanonicalizing([$firstTeam->id, $secondTeam->id]);

    $member->roles()->wherePivot('team_id', $firstTeam->id)->detach($role->id);

    expect($member->getTeams()->pluck('id')->all())->toBe([$secondTeam->id]);
    $this->assertDatabaseMissing('user_role', [
        'team_id' => $firstTeam->id,
        'user_id' => $member->id,
        'role_id' => $role->id,
    ]);
    $this->assertDatabaseHas('user_role', [
        'team_id' => $secondTeam->id,
        'user_id' => $member->id,
        'role_id' => $role->id,
    ]);
});

test('membership detach invalidation follows nested rollback boundaries', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Membership rollback requires teams enabled.');
    }

    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $team = $actor->currentTeam;
    $role = Role::factory()->create(['team_id' => $team->id]);
    $member = User::factory()->create(['current_team_id' => $team->id]);
    $member->roles()->attach($role->id, ['team_id' => $team->id]);
    $connection = $member->getConnection();
    $baselineLevel = $connection->transactionLevel();

    expect($member->getTeams()->pluck('id'))->toContain($team->id);

    $connection->beginTransaction();
    $connection->beginTransaction();

    try {
        $member->roles()->wherePivot('team_id', $team->id)->detach($role->id);
        expect($member->getTeams()->pluck('id'))->not->toContain($team->id);

        $connection->commit();
    } finally {
        while ($connection->transactionLevel() > $baselineLevel) {
            $connection->rollBack();
        }
    }

    expect($member->getTeams()->pluck('id'))->toContain($team->id);
});

test('membership detach closes warmed current-team option access and rollback restores it', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Current-team option access requires teams enabled.');
    }

    Cache::swap(serializedOptionCacheRepository());
    $actor = createSuperAdmin();
    $team = $actor->currentTeam;
    $role = Role::factory()->create(['team_id' => $team->id]);
    $member = User::factory()->create(['current_team_id' => $team->id]);
    $member->roles()->attach($role->id, ['team_id' => $team->id]);
    $this->actingAs($member);
    $team->updateOption('membership-context', 'visible');
    $connection = $member->getConnection();
    $baselineLevel = $connection->transactionLevel();

    expect($team->getOption('membership-context'))->toBe('visible');

    $connection->beginTransaction();

    try {
        $member->roles()->wherePivot('team_id', $team->id)->detach($role->id);

        Aura::flushState();
        expect(Team::query()->findOrFail($team->id)->getOption('membership-context'))->toBeNull();
    } finally {
        while ($connection->transactionLevel() > $baselineLevel) {
            $connection->rollBack();
        }
    }

    Aura::flushState();
    expect(Team::query()->findOrFail($team->id)->getOption('membership-context'))->toBe('visible');
});

test('team snapshots are transaction-local and rollback keeps the prior cached list valid', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->update(['name' => 'Committed name']);

    expect($user->getTeams()->firstWhere('id', $team->id)->name)->toBe('Committed name');

    DB::beginTransaction();

    try {
        $team->update(['name' => 'Uncommitted name']);

        expect($user->getTeams()->firstWhere('id', $team->id)->name)->toBe('Uncommitted name');
    } finally {
        DB::rollBack();
    }

    expect($user->getTeams()->firstWhere('id', $team->id)->name)->toBe('Committed name');
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

test('wildcard option reads preserve numeric suffix keys across legacy and canonical rows', function () {
    $user = createSuperAdmin();
    $attributes = [
        'name' => 'user.'.$user->id.'.numeric.10',
        'value' => ['source' => 'legacy'],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $user->current_team_id;
    }

    $option = Option::withoutGlobalScopes()->newModelInstance($attributes);
    $option->setAttribute(
        'owner_identity',
        VersionedCache::identity('option.user.owner', $user->getKey()),
    );
    $option->save();
    $user->updateOption('numeric.20', ['source' => 'canonical']);

    expect($user->getOption('numeric.*')->all())->toBe([
        10 => ['source' => 'legacy'],
        20 => ['source' => 'canonical'],
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

test('regular user team snapshots preserve membership pivot class and attributes', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $relationshipTeam = $user->teams()->firstOrFail();
    $expectedPivotClass = $relationshipTeam->pivot::class;
    $expectedPivotAttributes = $relationshipTeam->pivot->getAttributes();

    $assertMembership = function (Team $team) use ($expectedPivotClass, $expectedPivotAttributes): void {
        expect($team->relationLoaded('pivot'))->toBeTrue()
            ->and($team->pivot)->toBeInstanceOf($expectedPivotClass)
            ->and($team->pivot->getAttributes())->toBe($expectedPivotAttributes)
            ->and($team->pivot->role_id)->toBe($expectedPivotAttributes['role_id']);
    };

    $assertMembership($user->getTeams()->firstWhere('id', $relationshipTeam->id));
    $assertMembership($user->getTeams()->firstWhere('id', $relationshipTeam->id));
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

test('global admin team snapshots retain soft delete filtering', function () {
    Cache::swap(serializedOptionCacheRepository());
    $globalAdmin = createGlobalAdmin();
    $this->actingAs($globalAdmin);
    $team = foreignTeam();

    expect($globalAdmin->getTeams()->pluck('id'))->toContain($team->id);

    $team->delete();

    expect(Team::withTrashed()->findOrFail($team->id)->trashed())->toBeTrue()
        ->and($globalAdmin->getTeams()->pluck('id'))->not->toContain($team->id);

    $team->restore();

    expect($globalAdmin->getTeams()->pluck('id'))->toContain($team->id);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('user option reads require persisted authorization in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $user->updateOption('recent.records', ['contact:1']);

    expect($user->getOption('recent.records'))->toBe(['contact:1']);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($user->getOption('recent.records'))->toBe(
        config('aura.teams') ? null : ['contact:1'],
    );
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

test('team option reads fail closed without persisted authorization in a fresh application container', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $team = $user->currentTeam;
    $team->updateOption('settings', ['theme' => 'dark']);

    expect($team->getOption('settings'))->toBe(['theme' => 'dark']);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect($team->getOption('settings'))->toBeNull();
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('updating a team option invalidates Aura option reads', function () {
    Cache::swap(serializedOptionCacheRepository());
    createSuperAdmin();

    Aura::updateOption('settings', ['theme' => 'dark']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'dark']);

    Aura::updateOption('settings', ['theme' => 'light']);
    expect(Aura::getOption('settings'))->toBe(['theme' => 'light']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('team option reads require an authorized current team across users and requests', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $owner = createSuperAdmin();
    $firstTeam = $owner->currentTeam;
    $firstTeam->updateOption('tenant-secret', ['team' => 'first']);

    $secondTeam = Team::factory()->create();
    $secondTeam->updateOption('tenant-secret', ['team' => 'second']);
    $firstMember = soleMemberOf($firstTeam);
    $secondMember = soleMemberOf($secondTeam);

    foreach ([$firstTeam, $secondTeam] as $team) {
        Option::withoutGlobalScopes()->create([
            'team_id' => $team->id,
            'name' => 'legacy-unscoped-secret',
            'value' => ['team' => $team->id],
        ]);
    }

    $firstNoTeamUser = User::factory()->create(['current_team_id' => null]);
    $secondNoTeamUser = User::factory()->create(['current_team_id' => null]);
    $secondNoTeamUser->forceFill(['current_team_id' => $secondTeam->id])->saveQuietly();

    $this->actingAs($firstMember);

    expect($firstMember->authorizedCurrentTeam()?->is($firstTeam))->toBeTrue()
        ->and($firstTeam->getOption('tenant-secret'))->toBe(['team' => 'first'])
        ->and(Aura::getOption('tenant-secret'))->toBe(['team' => 'first'])
        ->and($secondTeam->getOption('tenant-secret'))->toBeNull();

    $this->actingAs($secondMember);

    expect($secondMember->authorizedCurrentTeam()?->is($secondTeam))->toBeTrue()
        ->and(Aura::getOption('tenant-secret'))->toBe(['team' => 'second'])
        ->and($firstTeam->getOption('tenant-secret'))->toBeNull();

    $this->actingAs($firstNoTeamUser);

    expect(Aura::getOption('legacy-unscoped-secret'))->toBe([])
        ->and($firstTeam->getOption('tenant-secret'))->toBeNull();

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($secondNoTeamUser);

    expect(Aura::getOption('legacy-unscoped-secret'))->toBe([])
        ->and(Aura::getOption('tenant-secret'))->toBe([])
        ->and($secondTeam->getOption('tenant-secret'))->toBeNull();
})->skip(fn () => ! config('aura.teams'), 'Authorized team option context requires teams enabled.');

test('teamless user option reads never expose exact or wildcard legacy rows', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $owner = createSuperAdmin();
    $firstTeam = $owner->currentTeam;
    $secondTeam = Team::factory()->create();
    $users = collect([
        soleMemberOf($firstTeam),
        soleMemberOf($secondTeam),
    ]);

    foreach ($users as $index => $user) {
        $this->actingAs($user);
        $user->updateOption('private.exact', ['user' => $index]);
        $user->updateOption('private.filters.mine', ['user' => $index]);

        expect($user->getOption('private.exact'))->toBe(['user' => $index])
            ->and($user->getOption('private.filters.*'))->not->toBeEmpty();

        $user->forceFill(['current_team_id' => null])->save();
    }

    $this->actingAs($users[0]);

    expect($users[0]->getOption('private.exact'))->toBeNull()
        ->and($users[0]->getOption('private.filters.*'))->toBeInstanceOf(Collection::class)
        ->and($users[0]->getOption('private.filters.*'))->toBeEmpty();

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($users[1]);

    expect($users[1]->getOption('private.exact'))->toBeNull()
        ->and($users[1]->getOption('private.filters.*'))->toBeInstanceOf(Collection::class)
        ->and($users[1]->getOption('private.filters.*'))->toBeEmpty();
})->skip(fn () => ! config('aura.teams'), 'Teamless option isolation requires teams enabled.');

test('team option cache remains isolated through updates deletes and fresh requests', function () {
    $cache = serializedOptionCacheRepository();
    Cache::swap($cache);

    $user = createSuperAdmin();
    $firstTeam = $user->currentTeam;
    $firstTeam->updateOption('settings', ['version' => 'first-1']);

    $secondTeam = Team::factory()->create();
    $secondTeam->updateOption('settings', ['version' => 'second-1']);
    expect($user->switchTeam($secondTeam))->toBeTrue();

    expect(Aura::getOption('settings'))->toBe(['version' => 'second-1'])
        ->and($user->switchTeam($firstTeam))->toBeTrue()
        ->and(Aura::getOption('settings'))->toBe(['version' => 'first-1']);

    $firstTeam->updateOption('settings', ['version' => 'first-2']);

    expect(Aura::getOption('settings'))->toBe(['version' => 'first-2']);

    $firstTeam->deleteOption('settings');

    expect(Aura::getOption('settings'))->toBe([])
        ->and($user->switchTeam($secondTeam))->toBeTrue()
        ->and(Aura::getOption('settings'))->toBe(['version' => 'second-1']);

    $secondTeam->updateOption('settings', ['version' => 'second-2']);

    expect(Aura::getOption('settings'))->toBe(['version' => 'second-2']);

    $secondTeam->deleteOption('settings');

    expect(Aura::getOption('settings'))->toBe([]);

    $secondTeam->updateOption('settings', ['version' => 'second-3']);
    expect(Aura::getOption('settings'))->toBe(['version' => 'second-3']);

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($user);

    expect(Aura::getOption('settings'))->toBe([]);
})->skip(fn () => ! config('aura.teams'), 'Team option cache isolation requires teams enabled.');

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

test('dotted user ids and option names have distinct physical identities', function () {
    Cache::swap(serializedOptionCacheRepository());
    Schema::create('adversarial_option_users', function (Blueprint $table): void {
        $table->string('id')->primary();
        $table->unsignedBigInteger('current_team_id')->nullable();
        $table->timestamps();
    });
    config()->set('aura.resources.user', AdversarialOptionUser::class);

    $teamId = config('aura.teams') ? createSuperAdmin()->current_team_id : null;
    foreach (['customer', 'customer.eu'] as $userId) {
        DB::table('adversarial_option_users')->insert([
            'id' => $userId,
            'current_team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $customer = AdversarialOptionUser::withoutGlobalScopes()->findOrFail('customer');
    $europeanCustomer = AdversarialOptionUser::withoutGlobalScopes()->findOrFail('customer.eu');

    $customer->updateOption('eu.secret', ['owner' => 'customer']);

    expect($europeanCustomer->getOption('secret'))->toBeNull();

    $europeanCustomer->updateOption('secret', ['owner' => 'customer.eu']);

    expect($customer->getOption('eu.secret'))->toBe(['owner' => 'customer'])
        ->and($europeanCustomer->getOption('secret'))->toBe(['owner' => 'customer.eu'])
        ->and(Option::withoutGlobalScopes()->count())->toBe(2);
});

test('numeric user options migrate legacy rows and invalidate legacy cache generations', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $teamId = config('aura.teams') ? $user->current_team_id : 'global';
    $legacyName = 'user.'.$user->id.'.legacy.setting';
    $legacyNamespace = 'option.user.'.$user->id.'.team.'.$teamId;
    $attributes = [
        'name' => $legacyName,
        'value' => ['version' => 1],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $teamId;
    }

    Option::withoutGlobalScopes()->create($attributes);

    $ownerIdentity = VersionedCache::identity('option.user.owner', $user->getKey());

    expect($user->getOption('legacy.setting'))->toBe(['version' => 1])
        ->and(Option::withoutGlobalScopes()->where('name', $legacyName)->value('owner_identity'))->toBe($ownerIdentity)
        ->and(VersionedCache::remember(
            $legacyNamespace,
            $legacyName,
            60,
            fn (): array => ['version' => 1],
        ))->toBe(['version' => 1]);

    $user->updateOption('legacy.setting', ['version' => 2]);

    expect($user->getOption('legacy.setting'))->toBe(['version' => 2])
        ->and(VersionedCache::remember(
            $legacyNamespace,
            $legacyName,
            60,
            fn (): array => ['version' => 2],
        ))->toBe(['version' => 2])
        ->and(Option::withoutGlobalScopes()->where('name', $legacyName)->exists())->toBeFalse()
        ->and(Option::withoutGlobalScopes()->count())->toBe(1)
        ->and(Option::withoutGlobalScopes()->value('name'))->toStartWith(User::optionNamePrefixFor($user->id))
        ->and(Option::withoutGlobalScopes()->value('owner_identity'))->toBe($ownerIdentity);
});

test('legacy verifier adoption rolls back with its surrounding transaction', function () {
    $user = createSuperAdmin();
    $legacyName = 'user.'.$user->id.'.transactional-legacy';
    $attributes = [
        'name' => $legacyName,
        'value' => ['preserved' => true],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $user->current_team_id;
    }

    Option::withoutGlobalScopes()->create($attributes);
    $connection = Option::withoutGlobalScopes()->getModel()->getConnection();
    $baselineLevel = $connection->transactionLevel();
    $connection->beginTransaction();

    try {
        expect($user->getOption('transactional-legacy'))->toBe(['preserved' => true])
            ->and(Option::withoutGlobalScopes()->value('owner_identity'))->toBe(
                VersionedCache::identity('option.user.owner', $user->getKey()),
            );
    } finally {
        while ($connection->transactionLevel() > $baselineLevel) {
            $connection->rollBack();
        }
    }

    expect(Option::withoutGlobalScopes()->value('owner_identity'))->toBeNull();
});

test('version two user option identities migrate safely to the compact canonical identity', function () {
    $user = createSuperAdmin();
    $option = 'legacy-v2';
    $versionTwoName = 'aura-user-option-v2:'
        .VersionedCache::identity('option.user.owner', $user->id)
        .':'
        .$option;
    $attributes = [
        'name' => $versionTwoName,
        'value' => ['version' => 2],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $user->current_team_id;
    }

    Option::withoutGlobalScopes()->create($attributes);

    $ownerIdentity = VersionedCache::identity('option.user.owner', $user->getKey());

    expect($user->getOption($option))->toBe(['version' => 2])
        ->and(Option::withoutGlobalScopes()->where('name', $versionTwoName)->value('owner_identity'))->toBe($ownerIdentity);

    $user->updateOption($option, ['version' => 3]);

    $physicalName = Option::withoutGlobalScopes()->sole()->getRawOriginal('name');

    expect($user->getOption($option))->toBe(['version' => 3])
        ->and($physicalName)->toBe(User::optionNamePrefixFor($user->id).$option)
        ->and(strlen($physicalName))->toBeLessThanOrEqual(255)
        ->and(Option::withoutGlobalScopes()->value('owner_identity'))->toBe($ownerIdentity)
        ->and(Option::withoutGlobalScopes()->withTrashed()->where('name', $versionTwoName)->exists())->toBeFalse();
});

test('a compact collision blocks legacy migration before either row is claimed or mutated', function () {
    [$firstUser, $secondUser] = collidingOptionUsers();
    $option = 'private.migration-collision';
    $versionTwoName = 'aura-user-option-v2:'
        .VersionedCache::identity('option.user.owner', $firstUser->getKey())
        .':'
        .$option;
    $attributes = [
        'name' => $versionTwoName,
        'value' => ['owner' => 'first'],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $firstUser->current_team_id;
    }

    Option::withoutGlobalScopes()->create($attributes);
    $secondUser->updateOption($option, ['owner' => 'second']);

    expect(fn () => $firstUser->updateOption($option, ['owner' => 'first-updated']))
        ->toThrow(OptionOwnerIdentityException::class)
        ->and(Option::withoutGlobalScopes()->where('name', $versionTwoName)->value('owner_identity'))->toBeNull()
        ->and(Option::withoutGlobalScopes()->where('name', $versionTwoName)->value('value'))->toBe(['owner' => 'first'])
        ->and($secondUser->getOption($option))->toBe(['owner' => 'second']);
});

test('a mismatched alias blocks canonical update and delete cleanup atomically', function (string $operation) {
    $user = createSuperAdmin();
    $option = 'conflicting-alias';
    $user->updateOption($option, ['version' => 1]);
    $alias = Option::withoutGlobalScopes()->newModelInstance([
        'name' => 'aura-user-option-v2:'
            .VersionedCache::identity('option.user.owner', $user->getKey())
            .':'
            .$option,
        'value' => ['version' => 0],
    ]);

    if (config('aura.teams')) {
        $alias->setAttribute('team_id', $user->current_team_id);
    }

    $alias->setAttribute(
        'owner_identity',
        VersionedCache::identity('option.user.owner', 'different-owner-secret'),
    );
    $alias->save();

    $attempt = match ($operation) {
        'update' => fn () => $user->updateOption($option, ['version' => 2]),
        'delete' => fn () => $user->deleteOption($option),
    };

    expect($attempt)->toThrow(OptionOwnerIdentityException::class)
        ->and(Option::withoutGlobalScopes()->withTrashed()->count())->toBe(2)
        ->and($user->getOption($option))->toBe(['version' => 1]);
})->with(['update', 'delete']);

test('canonical numeric user writes remove stale legacy aliases', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('mixed-deployment', ['version' => 1]);
    $legacyName = 'user.'.$user->id.'.mixed-deployment';
    $attributes = [
        'name' => $legacyName,
        'value' => ['version' => 0],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $user->current_team_id;
    }

    Option::withoutGlobalScopes()->create($attributes);
    $user->updateOption('mixed-deployment', ['version' => 2]);

    expect($user->getOption('mixed-deployment'))->toBe(['version' => 2])
        ->and(Option::withoutGlobalScopes()->withTrashed()->where('name', $legacyName)->exists())->toBeFalse()
        ->and(Option::withoutGlobalScopes()->withTrashed()->count())->toBe(1);
});

test('direct option model updates invalidate warmed user option values', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('direct-model', ['version' => 1]);

    expect($user->getOption('direct-model'))->toBe(['version' => 1]);

    $option = Option::withoutGlobalScopes()->sole();
    $option->update(['value' => ['version' => 2]]);

    expect($user->getOption('direct-model'))->toBe(['version' => 2]);
});

test('the generic resource editor invalidates warmed option values', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('resource-editor', ['version' => 1]);
    $option = Option::withoutGlobalScopes()->sole();

    expect($user->getOption('resource-editor'))->toBe(['version' => 1]);

    livewire(Edit::class, ['slug' => 'option', 'id' => $option->id])
        ->set('form.fields.value', ['version' => 2])
        ->call('save')
        ->assertHasNoErrors();

    expect($user->getOption('resource-editor'))->toBe(['version' => 2]);
});

test('direct option model updates invalidate warmed team option values', function () {
    Cache::swap(serializedOptionCacheRepository());
    $team = createSuperAdmin()->currentTeam;
    $team->updateOption('direct-model', ['version' => 1]);

    expect($team->getOption('direct-model'))->toBe(['version' => 1]);

    Option::withoutGlobalScopes()->sole()->update(['value' => ['version' => 2]]);

    expect($team->getOption('direct-model'))->toBe(['version' => 2]);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('direct option model updates invalidate warmed global option values', function () {
    Cache::swap(serializedOptionCacheRepository());
    Aura::updateOption('direct-model', ['version' => 1]);

    expect(Aura::getOption('direct-model'))->toBe(['version' => 1]);

    Option::withoutGlobalScopes()->sole()->update(['value' => ['version' => 2]]);

    expect(Aura::getOption('direct-model'))->toBe(['version' => 2]);
})->skip(fn () => config('aura.teams'), 'Global option context requires teams-off mode.');

test('direct option model creation invalidates a cached user option miss', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();

    expect($user->getOption('direct-create'))->toBeNull();

    $attributes = [
        'name' => User::optionNamePrefixFor($user->id).'direct-create',
        'value' => ['created' => true],
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $user->current_team_id;
    }

    $option = Option::withoutGlobalScopes()->newModelInstance($attributes);
    $option->setAttribute(
        'owner_identity',
        VersionedCache::identity('option.user.owner', $user->getKey()),
    );
    $option->save();

    expect($user->getOption('direct-create'))->toBe(['created' => true]);
});

test('direct option name and user changes invalidate old and new cached identities', function () {
    Cache::swap(serializedOptionCacheRepository());
    $firstUser = createSuperAdmin();
    $secondUser = config('aura.teams')
        ? soleMemberOf($firstUser->currentTeam)
        : createSuperAdminWithoutTeam();
    $firstUser->updateOption('direct-owner-change', ['owner' => 'first']);

    expect($firstUser->getOption('direct-owner-change'))->toBe(['owner' => 'first'])
        ->and($secondUser->getOption('direct-owner-change'))->toBeNull();

    $option = Option::withoutGlobalScopes()->sole();
    $option->forceFill([
        'name' => User::optionNamePrefixFor($secondUser->id).'direct-owner-change',
        'owner_identity' => VersionedCache::identity('option.user.owner', $secondUser->getKey()),
        'value' => ['owner' => 'second'],
    ])->save();

    expect($firstUser->getOption('direct-owner-change'))->toBeNull()
        ->and($secondUser->getOption('direct-owner-change'))->toBe(['owner' => 'second']);
});

test('direct option team changes invalidate old and new cached scopes', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $firstTeam = $user->currentTeam;
    $user->updateOption('direct-team-change', ['team' => 'first']);
    $secondTeam = Team::factory()->create();

    expect($user->switchTeam($firstTeam))->toBeTrue()
        ->and($user->getOption('direct-team-change'))->toBe(['team' => 'first'])
        ->and($user->switchTeam($secondTeam))->toBeTrue()
        ->and($user->getOption('direct-team-change'))->toBeNull();

    $option = Option::withoutGlobalScopes()->sole();
    $option->update(['team_id' => $secondTeam->id]);

    expect($user->getOption('direct-team-change'))->toBe(['team' => 'first'])
        ->and($user->switchTeam($firstTeam))->toBeTrue()
        ->and($user->getOption('direct-team-change'))->toBeNull();
})->skip(fn () => ! config('aura.teams'), 'Option team changes require teams enabled.');

test('rolled back direct option updates neither publish nor invalidate committed cache state', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('direct-rollback', ['version' => 1]);
    $option = Option::withoutGlobalScopes()->sole();

    expect($user->getOption('direct-rollback'))->toBe(['version' => 1]);

    DB::beginTransaction();

    try {
        $option->update(['value' => ['version' => 2]]);

        expect($user->getOption('direct-rollback'))->toBe(['version' => 2]);
    } finally {
        DB::rollBack();
    }

    expect($user->getOption('direct-rollback'))->toBe(['version' => 1]);
});

test('direct option deletion retains a restorable row while hiding its cached value', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('restorable-model', ['version' => 1]);
    $option = Option::withoutGlobalScopes()->sole();

    expect($user->getOption('restorable-model'))->toBe(['version' => 1]);

    $option->delete();

    expect($user->getOption('restorable-model'))->toBeNull()
        ->and(DB::table('options')->where('id', $option->id)->exists())->toBeTrue();

    $option->restore();

    expect($user->getOption('restorable-model'))->toBe(['version' => 1]);
});

test('direct option force deletion invalidates and removes its cached value', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('force-delete-model', ['version' => 1]);
    $option = Option::withoutGlobalScopes()->sole();

    expect($user->getOption('force-delete-model'))->toBe(['version' => 1]);

    $option->forceDelete();

    expect($user->getOption('force-delete-model'))->toBeNull()
        ->and(DB::table('options')->where('id', $option->id)->exists())->toBeFalse();
});

test('updating a soft deleted user option restores its unique physical identity', function () {
    Cache::swap(serializedOptionCacheRepository());
    $user = createSuperAdmin();
    $user->updateOption('restore-on-write', ['version' => 1]);
    $user->deleteOption('restore-on-write');

    expect($user->getOption('restore-on-write'))->toBeNull();

    $user->updateOption('restore-on-write', ['version' => 2]);

    expect($user->getOption('restore-on-write'))->toBe(['version' => 2])
        ->and(Option::withoutGlobalScopes()->withTrashed()->count())->toBe(1);
});

test('updating a soft deleted team option restores its unique physical identity', function () {
    Cache::swap(serializedOptionCacheRepository());
    $team = createSuperAdmin()->currentTeam;
    $team->updateOption('restore-on-write', ['version' => 1]);
    $team->deleteOption('restore-on-write');

    expect($team->getOption('restore-on-write'))->toBeNull();

    $team->updateOption('restore-on-write', ['version' => 2]);

    expect($team->getOption('restore-on-write'))->toBe(['version' => 2])
        ->and(Option::withoutGlobalScopes()->withTrashed()->count())->toBe(1);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('updating a soft deleted global option restores its unique physical identity', function () {
    Cache::swap(serializedOptionCacheRepository());
    Aura::updateOption('restore-on-write', ['version' => 1]);
    Option::withoutGlobalScopes()->sole()->delete();

    expect(Aura::getOption('restore-on-write'))->toBe([]);

    Aura::updateOption('restore-on-write', ['version' => 2]);

    expect(Aura::getOption('restore-on-write'))->toBe(['version' => 2])
        ->and(Option::withoutGlobalScopes()->withTrashed()->count())->toBe(1);
})->skip(fn () => config('aura.teams'), 'Global option context requires teams-off mode.');

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

test('wildcard team options fail closed without persisted authorization in a fresh application container', function () {
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
        ->all()->toBe([]);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('team option reads use the team instance context', function () {
    Cache::swap(serializedOptionCacheRepository());

    $user = createSuperAdmin();
    $firstTeam = $user->currentTeam;
    $firstTeam->updateOption('settings', ['theme' => 'red']);

    $secondTeam = Team::factory()->create();
    $secondTeam->updateOption('settings', ['theme' => 'blue']);

    $firstTeam->clearCachedOption('settings');

    expect($firstTeam->getOption('settings'))->toBeNull()
        ->and($secondTeam->getOption('settings'))->toBe(['theme' => 'blue'])
        ->and($user->switchTeam($firstTeam))->toBeTrue()
        ->and($firstTeam->getOption('settings'))->toBe(['theme' => 'red'])
        ->and($secondTeam->getOption('settings'))->toBeNull();
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

test('user option reads require the authenticated team context', function () {
    Cache::swap(serializedOptionCacheRepository());

    $firstUser = createSuperAdmin();
    $firstUser->updateOption('columns.Contact', ['name']);

    $secondTeam = Team::factory()->createQuietly(['user_id' => $firstUser->id]);
    $secondUser = soleMemberOf($secondTeam);
    $this->actingAs($secondUser);

    $firstUser->clearCachedOption('columns.Contact');

    expect($firstUser->getOption('columns.Contact'))->toBeNull();

    $this->actingAs($firstUser);

    expect($firstUser->getOption('columns.Contact'))->toBe(['name']);
})->skip(fn () => ! config('aura.teams'), 'Team option context requires teams enabled.');

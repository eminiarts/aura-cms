<?php

use Aura\Base\Database\GuardedDatabaseManager;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

class ExplicitNullSharedPost extends Resource
{
    public static bool $sharedAcrossTeams = true;

    public static string $type = 'ExplicitNullSharedPost';
}

class ExplicitNullSharedCustomResource extends Resource
{
    public static $customTable = true;

    public static bool $sharedAcrossTeams = true;

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'team_id', 'user_id'];

    protected $table = 'explicit_null_shared_custom_resources';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
            ],
        ];
    }
}

class ThrowingGlobalCustomResource extends ExplicitNullSharedCustomResource
{
    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (): void {
            throw new Error('global write failure');
        });
    }
}

class PhysicalWriterGuardedGlobalResource extends ExplicitNullSharedCustomResource
{
    public static ?Closure $creatingAttack = null;

    public static ?Closure $savingAttack = null;

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (self $resource): void {
            if (self::$savingAttack !== null) {
                (self::$savingAttack)($resource);
            }
        });

        static::creating(function (self $resource): void {
            if (self::$creatingAttack !== null) {
                (self::$creatingAttack)($resource);
            }
        });
    }
}

class NestedNonSharedTag extends Resource
{
    public static string $type = 'NestedNonSharedTag';
}

class CrossConnectionNestedNonSharedTag extends Resource
{
    public static string $type = 'CrossConnectionNestedNonSharedTag';

    protected $connection = 'nested_global_write';
}

class SharedGlobalResourceWithTags extends Resource
{
    public static bool $sharedAcrossTeams = true;

    public static string $type = 'SharedGlobalResourceWithTags';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => NestedNonSharedTag::class,
            ],
        ];
    }
}

class CrossConnectionSharedGlobalResourceWithTags extends Resource
{
    public static bool $sharedAcrossTeams = true;

    public static string $type = 'CrossConnectionSharedGlobalResourceWithTags';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => CrossConnectionNestedNonSharedTag::class,
            ],
        ];
    }
}

beforeEach(function () {
    if (! Schema::hasColumn('posts', 'team_id')) {
        $this->markTestSkipped('Initial team defaults require the teams schema.');
    }

    Schema::create('explicit_null_shared_custom_resources', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('team_id')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    PhysicalWriterGuardedGlobalResource::$savingAttack = null;
    PhysicalWriterGuardedGlobalResource::$creatingAttack = null;
    Schema::dropIfExists('explicit_null_shared_custom_resources');
    DB::purge('nested_global_write');
    DB::purge('global_write_reconnect');
});

function core13PhysicalWriter(): PDO
{
    $writer = new PDO('sqlite::memory:');
    $writer->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $writer->exec(<<<'SQL'
        CREATE TABLE explicit_null_shared_custom_resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR NOT NULL,
            team_id INTEGER NULL,
            user_id INTEGER NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )
        SQL);

    return $writer;
}

function core13PhysicalWriterRowCount(PDO $writer): int
{
    return (int) $writer
        ->query('SELECT COUNT(*) FROM explicit_null_shared_custom_resources')
        ->fetchColumn();
}

it('rejects explicitly null team and creator values in ordinary creates', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    expect(fn () => Post::withoutGlobalScopes()->create([
        'title' => 'Unowned global candidate',
        'team_id' => null,
        'user_id' => null,
    ]))->toThrow(LogicException::class);

    expect(fn () => ExplicitNullSharedCustomResource::withoutGlobalScopes()->create([
        'name' => 'Ordinary custom create',
        'team_id' => null,
        'user_id' => null,
    ]))->toThrow(LogicException::class);
});

it('defaults omitted team and creator values from the authenticated user', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $post = Post::withoutGlobalScopes()->create([
        'title' => 'Owned team post',
    ]);

    $post = Post::withoutGlobalScopes()->findOrFail($post->id);

    expect($post->getAttribute('team_id'))->toBe($actor->current_team_id)
        ->and($post->getAttribute('user_id'))->toBe($actor->id);
});

it('preserves explicit null tenancy and ownership through the privileged global create contract', function () {
    $globalAdmin = createSuperAdmin();
    $globalAdmin->forceFill(['global_admin' => true])->saveQuietly();
    $this->actingAs($globalAdmin->refresh());

    $post = ExplicitNullSharedPost::createGlobal([
        'title' => 'Privileged global post',
        'user_id' => null,
    ]);
    $custom = ExplicitNullSharedCustomResource::createGlobal([
        'name' => 'Privileged global custom row',
        'user_id' => null,
    ]);

    expect($post->getAttribute('team_id'))->toBeNull()
        ->and($post->getAttribute('user_id'))->toBeNull()
        ->and($custom->getAttribute('team_id'))->toBeNull()
        ->and($custom->getAttribute('user_id'))->toBeNull();
});

it('refuses the privileged global create contract to a team admin', function () {
    $teamAdmin = createSuperAdmin();
    $this->actingAs($teamAdmin);

    expect(fn () => ExplicitNullSharedCustomResource::createGlobal([
        'name' => 'Forbidden global custom row',
    ]))->toThrow(AuthorizationException::class);
});

it('requires an explicit trusted contract for unauthenticated global creation', function () {
    auth()->logout();

    expect(fn () => ExplicitNullSharedCustomResource::withoutGlobalScopes()->create([
        'name' => 'Accidental background global row',
        'team_id' => null,
    ]))->toThrow(LogicException::class, 'Use createGlobal() or createGlobalForSystem()');

    $global = ExplicitNullSharedCustomResource::createGlobalForSystem([
        'name' => 'Intentional background global row',
    ]);
    $firstOrCreated = ExplicitNullSharedCustomResource::firstOrCreateGlobalForSystem(
        ['name' => 'Intentional first-or-create row'],
        ['team_id' => 12345],
    );

    expect($global->getAttribute('team_id'))->toBeNull()
        ->and($firstOrCreated->getAttribute('team_id'))->toBeNull();
});

it('updates a global custom row through the trusted system contract', function () {
    $global = ExplicitNullSharedCustomResource::createGlobalForSystem([
        'name' => 'Old catalog value',
    ]);

    $updated = ExplicitNullSharedCustomResource::updateOrCreateGlobalForSystem(
        ['id' => $global->id],
        ['name' => 'New catalog value', 'team_id' => 12345],
    );

    expect($updated->id)->toBe($global->id)
        ->and($updated->name)->toBe('New catalog value')
        ->and($updated->getAttribute('team_id'))->toBeNull();
});

it('restores the global-write invariant after a model event throws an Error', function () {
    expect(fn () => ThrowingGlobalCustomResource::createGlobalForSystem([
        'name' => 'Throwing global row',
    ]))->toThrow(Error::class, 'global write failure');

    expect(Resource::isGlobalWriteInProgress())->toBeFalse();
});

it('fails closed before the outer global insert when a saving listener swaps the physical writer', function () {
    $connection = DB::connection();
    $originalWriter = $connection->getPdo();
    $substitutedWriter = core13PhysicalWriter();

    PhysicalWriterGuardedGlobalResource::$savingAttack = function (PhysicalWriterGuardedGlobalResource $resource) use ($substitutedWriter): void {
        $resource->getConnection()->setPdo($substitutedWriter);
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => 'Redirected outer write',
        ]))->toThrow(LogicException::class, 'physical database writer');
    } finally {
        PhysicalWriterGuardedGlobalResource::$savingAttack = null;
        $connection->setPdo($originalWriter);
    }

    expect(core13PhysicalWriterRowCount($originalWriter))->toBe(0)
        ->and(core13PhysicalWriterRowCount($substitutedWriter))->toBe(0);
});

it('fails closed when a creating listener swaps the writer after the saving event', function () {
    $connection = DB::connection();
    $originalWriter = $connection->getPdo();
    $substitutedWriter = core13PhysicalWriter();

    PhysicalWriterGuardedGlobalResource::$creatingAttack = function (PhysicalWriterGuardedGlobalResource $resource) use ($substitutedWriter): void {
        $resource->getConnection()->setPdo($substitutedWriter);
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => 'Redirected creating write',
        ]))->toThrow(LogicException::class, 'physical database writer');
    } finally {
        PhysicalWriterGuardedGlobalResource::$creatingAttack = null;
        $connection->setPdo($originalWriter);
    }

    expect(core13PhysicalWriterRowCount($originalWriter))->toBe(0)
        ->and(core13PhysicalWriterRowCount($substitutedWriter))->toBe(0);
});

it('blocks a saving listener from issuing a query on the substituted writer', function () {
    $connection = DB::connection();
    $originalWriter = $connection->getPdo();
    $substitutedWriter = core13PhysicalWriter();

    PhysicalWriterGuardedGlobalResource::$savingAttack = function (PhysicalWriterGuardedGlobalResource $resource) use ($substitutedWriter): void {
        $connection = $resource->getConnection();
        $connection->setPdo($substitutedWriter);
        $connection->table('explicit_null_shared_custom_resources')->insert([
            'name' => 'Listener side-channel write',
        ]);
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => 'Outer side-channel candidate',
        ]))->toThrow(LogicException::class, 'physical database writer');
    } finally {
        PhysicalWriterGuardedGlobalResource::$savingAttack = null;
        $connection->setPdo($originalWriter);
    }

    expect(core13PhysicalWriterRowCount($originalWriter))->toBe(0)
        ->and(core13PhysicalWriterRowCount($substitutedWriter))->toBe(0);
});

it('fails closed when a saving listener swaps the writer and re-enters save on the same resource', function () {
    $connection = DB::connection();
    $originalWriter = $connection->getPdo();
    $substitutedWriter = core13PhysicalWriter();
    $reentered = false;

    PhysicalWriterGuardedGlobalResource::$savingAttack = function (PhysicalWriterGuardedGlobalResource $resource) use ($substitutedWriter, &$reentered): void {
        if ($reentered) {
            return;
        }

        $reentered = true;
        $resource->getConnection()->setPdo($substitutedWriter);
        $resource->save();
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => 'Reentrant redirected write',
        ]))->toThrow(LogicException::class, 'physical database writer');
    } finally {
        PhysicalWriterGuardedGlobalResource::$savingAttack = null;
        $connection->setPdo($originalWriter);
    }

    expect($reentered)->toBeTrue()
        ->and(core13PhysicalWriterRowCount($originalWriter))->toBe(0)
        ->and(core13PhysicalWriterRowCount($substitutedWriter))->toBe(0);
});

it('cleans physical writer authority after an attack throws and permits a later global write', function () {
    $connection = DB::connection();
    $originalWriter = $connection->getPdo();
    $substitutedWriter = core13PhysicalWriter();

    PhysicalWriterGuardedGlobalResource::$savingAttack = function (PhysicalWriterGuardedGlobalResource $resource) use ($substitutedWriter): void {
        $resource->getConnection()->setPdo($substitutedWriter);

        throw new Error('physical writer attack');
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => 'Throwing redirected write',
        ]))->toThrow(Error::class, 'physical writer attack');
    } finally {
        PhysicalWriterGuardedGlobalResource::$savingAttack = null;
        $connection->setPdo($originalWriter);
    }

    expect(fn () => PhysicalWriterGuardedGlobalResource::withoutGlobalScopes()->create([
        'name' => 'Capability leak candidate',
        'team_id' => null,
    ]))->toThrow(LogicException::class, 'Use createGlobal() or createGlobalForSystem()');

    $recovered = PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
        'name' => 'Recovered global write',
    ]);

    expect($recovered->name)->toBe('Recovered global write')
        ->and(core13PhysicalWriterRowCount($originalWriter))->toBe(1)
        ->and(core13PhysicalWriterRowCount($substitutedWriter))->toBe(0);
});

it('invalidates global writer authority when the connection is reconnected or purged', function (string $attack): void {
    $database = tempnam(sys_get_temp_dir(), 'aura-core13-writer-');

    if ($database === false) {
        throw new RuntimeException('Unable to create a temporary SQLite database.');
    }

    config()->set('database.connections.global_write_reconnect', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);
    DB::purge('global_write_reconnect');

    $connection = DB::connection('global_write_reconnect');
    $connection->getPdo()->exec(<<<'SQL'
        CREATE TABLE explicit_null_shared_custom_resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR NOT NULL,
            team_id INTEGER NULL,
            user_id INTEGER NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )
        SQL);
    $originalWriter = $connection->getPdo();

    PhysicalWriterGuardedGlobalResource::$savingAttack = function () use ($attack): void {
        if ($attack === 'reconnect') {
            DB::reconnect('global_write_reconnect');

            return;
        }

        DB::purge('global_write_reconnect');
        DB::connection('global_write_reconnect');
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => ucfirst($attack).' redirected write',
        ], $connection))->toThrow(LogicException::class, 'physical database writer');

        $currentConnection = DB::connection('global_write_reconnect');

        expect($currentConnection->getPdo())->not->toBe($originalWriter)
            ->and((int) $currentConnection->table('explicit_null_shared_custom_resources')->count())->toBe(0);

        PhysicalWriterGuardedGlobalResource::$savingAttack = null;
        $recovered = PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => ucfirst($attack).' recovery',
        ], $currentConnection);

        expect($recovered->name)->toBe(ucfirst($attack).' recovery')
            ->and((int) $currentConnection->table('explicit_null_shared_custom_resources')->count())->toBe(1);
    } finally {
        PhysicalWriterGuardedGlobalResource::$savingAttack = null;
        DB::purge('global_write_reconnect');
        @unlink($database);
    }
})->with(['reconnect', 'purge']);

it('blocks a purged same-name replacement connection from writing during a global save', function (): void {
    expect(DB::getFacadeRoot())->toBeInstanceOf(GuardedDatabaseManager::class)
        ->and(app(DatabaseManager::class))->toBe(DB::getFacadeRoot());

    $database = tempnam(sys_get_temp_dir(), 'aura-core13-purge-write-');

    if ($database === false) {
        throw new RuntimeException('Unable to create a temporary SQLite database.');
    }

    config()->set('database.connections.global_write_reconnect', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);
    DB::purge('global_write_reconnect');

    $originalConnection = DB::connection('global_write_reconnect');
    $originalConnection->getPdo()->exec(<<<'SQL'
        CREATE TABLE explicit_null_shared_custom_resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR NOT NULL,
            team_id INTEGER NULL,
            user_id INTEGER NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )
        SQL);
    $originalWriter = $originalConnection->getPdo();
    $replacementConnection = null;
    Event::forget(ConnectionEstablished::class);

    PhysicalWriterGuardedGlobalResource::$savingAttack = function () use (&$replacementConnection): void {
        DB::purge('global_write_reconnect');
        $replacementConnection = DB::connection('global_write_reconnect');
        $replacementConnection->getPdo()->exec(<<<'SQL'
            INSERT INTO explicit_null_shared_custom_resources (name)
            VALUES ('Replacement listener raw PDO side-channel write')
            SQL);
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => 'Outer purge candidate',
        ], $originalConnection))->toThrow(LogicException::class, 'physical database writer');

        expect($replacementConnection)->toBeNull();

        $inspectionConnection = DB::connection('global_write_reconnect');

        expect($inspectionConnection)->not->toBe($originalConnection)
            ->and($inspectionConnection->getPdo())->not->toBe($originalWriter)
            ->and((int) $inspectionConnection->table('explicit_null_shared_custom_resources')->count())->toBe(0);
    } finally {
        PhysicalWriterGuardedGlobalResource::$savingAttack = null;
        DB::purge('global_write_reconnect');
        @unlink($database);
    }
});

it('blocks a late connection resolver from replacing the guarded writer during a global save', function (): void {
    $database = tempnam(sys_get_temp_dir(), 'aura-core13-late-resolver-');

    if ($database === false) {
        throw new RuntimeException('Unable to create a temporary SQLite database.');
    }

    config()->set('database.connections.global_write_reconnect', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);
    DB::purge('global_write_reconnect');

    $originalConnection = DB::connection('global_write_reconnect');
    $originalConnection->getPdo()->exec(<<<'SQL'
        CREATE TABLE explicit_null_shared_custom_resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR NOT NULL,
            team_id INTEGER NULL,
            user_id INTEGER NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )
        SQL);
    $originalWriter = $originalConnection->getPdo();
    $originalResolver = Connection::getResolver('sqlite');
    $replacementConnection = null;

    PhysicalWriterGuardedGlobalResource::$savingAttack = function () use (&$replacementConnection): void {
        Connection::resolverFor(
            'sqlite',
            static fn (mixed $pdo, string $database, string $prefix, array $config): SQLiteConnection => new SQLiteConnection($pdo, $database, $prefix, $config),
        );
        DB::purge('global_write_reconnect');
        $replacementConnection = DB::connection('global_write_reconnect');
        $replacementConnection->table('explicit_null_shared_custom_resources')->insert([
            'name' => 'Late resolver side-channel write',
        ]);
    };

    try {
        expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
            'name' => 'Outer late resolver candidate',
        ], $originalConnection))->toThrow(LogicException::class, 'physical database writer');

        expect($replacementConnection)->toBeNull();

        $inspectionConnection = DB::connection('global_write_reconnect');

        expect($inspectionConnection)->not->toBe($originalConnection)
            ->and($inspectionConnection->getPdo())->not->toBe($originalWriter)
            ->and((int) $inspectionConnection->table('explicit_null_shared_custom_resources')->count())->toBe(0);
    } finally {
        PhysicalWriterGuardedGlobalResource::$savingAttack = null;

        if ($originalResolver instanceof Closure) {
            Connection::resolverFor('sqlite', $originalResolver);
        }

        DB::purge('global_write_reconnect');
        @unlink($database);
    }
});

it('does not leak global writer authority across repeated long worker failures', function () {
    $connection = DB::connection();
    $originalWriter = $connection->getPdo();
    $substitutedWriters = [];

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $substitutedWriter = core13PhysicalWriter();
        $substitutedWriters[] = $substitutedWriter;

        PhysicalWriterGuardedGlobalResource::$savingAttack = function (PhysicalWriterGuardedGlobalResource $resource) use ($substitutedWriter): void {
            $resource->getConnection()->setPdo($substitutedWriter);
        };

        try {
            expect(fn () => PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
                'name' => 'Long worker attack '.$attempt,
            ]))->toThrow(LogicException::class, 'physical database writer');
        } finally {
            $connection->setPdo($originalWriter);
        }
    }

    PhysicalWriterGuardedGlobalResource::$savingAttack = null;
    $recovered = PhysicalWriterGuardedGlobalResource::createGlobalForSystem([
        'name' => 'Long worker recovery',
    ]);

    expect($recovered->name)->toBe('Long worker recovery')
        ->and(core13PhysicalWriterRowCount($originalWriter))->toBe(1);

    foreach ($substitutedWriters as $substitutedWriter) {
        expect(core13PhysicalWriterRowCount($substitutedWriter))->toBe(0);
    }
});

it('does not grant a nested Tags save the parent global-write capability', function (string $api) {
    if ($api === 'authorized') {
        $globalAdmin = createGlobalAdmin(['current_team_id' => null]);
        $this->actingAs($globalAdmin);
    } else {
        auth()->logout();
    }

    $create = fn () => match ($api) {
        'authorized' => SharedGlobalResourceWithTags::createGlobal([
            'title' => 'Authorized global parent',
            'tags' => ['Nested authorized leak'],
        ]),
        'system-create' => SharedGlobalResourceWithTags::createGlobalForSystem([
            'title' => 'System global parent',
            'tags' => ['Nested system leak'],
        ]),
        'system-first-or-create' => SharedGlobalResourceWithTags::firstOrCreateGlobalForSystem(
            ['title' => 'System first-or-create parent'],
            ['tags' => ['Nested system first-or-create leak']],
        ),
        'system-update-or-create' => SharedGlobalResourceWithTags::updateOrCreateGlobalForSystem(
            ['title' => 'System update-or-create parent'],
            ['tags' => ['Nested system update-or-create leak']],
        ),
    };

    expect($create)->toThrow(LogicException::class);
    expect(NestedNonSharedTag::withoutGlobalScopes()
        ->where('type', NestedNonSharedTag::$type)
        ->count())->toBe(0);
})->with(['authorized', 'system-create', 'system-first-or-create', 'system-update-or-create']);

it('does not grant a nested Tags save authority on another connection', function (string $api) {
    config()->set('database.connections.nested_global_write', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);
    DB::purge('nested_global_write');

    Schema::connection('nested_global_write')->create('posts', function (Blueprint $table): void {
        $table->id();
        $table->text('title')->nullable();
        $table->longText('content')->nullable();
        $table->string('slug')->nullable();
        $table->string('type', 64);
        $table->string('status', 20)->default('publish')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    if ($api === 'authorized') {
        $globalAdmin = createGlobalAdmin(['current_team_id' => null]);
        $this->actingAs($globalAdmin);
    } else {
        auth()->logout();
    }

    $create = fn () => match ($api) {
        'authorized' => CrossConnectionSharedGlobalResourceWithTags::createGlobal([
            'title' => 'Authorized cross-connection parent',
            'tags' => ['Nested authorized cross-connection leak'],
        ]),
        'system' => CrossConnectionSharedGlobalResourceWithTags::createGlobalForSystem([
            'title' => 'System cross-connection parent',
            'tags' => ['Nested system cross-connection leak'],
        ]),
    };

    expect($create)->toThrow(LogicException::class);
    expect(DB::connection('nested_global_write')
        ->table('posts')
        ->where('type', CrossConnectionNestedNonSharedTag::$type)
        ->count())->toBe(0);
})->with(['authorized', 'system']);

it('rejects foreign tenancy and ownership during ordinary post and role creates', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->createQuietly(['user_id' => $otherOwner->id]);

    expect(fn () => Post::withoutGlobalScopes()->create([
        'title' => 'Foreign team injection',
        'team_id' => $otherTeam->id,
        'user_id' => $actor->id,
    ]))->toThrow(LogicException::class);

    expect(fn () => Post::withoutGlobalScopes()->create([
        'title' => 'Foreign owner injection',
        'team_id' => $actor->current_team_id,
        'user_id' => $otherOwner->id,
    ]))->toThrow(LogicException::class);

    expect(fn () => Role::withoutGlobalScopes()->create([
        'name' => 'Foreign role',
        'slug' => 'foreign-role',
        'team_id' => $otherTeam->id,
        'permissions' => [],
    ]))->toThrow(LogicException::class);

    $owned = Post::withoutGlobalScopes()->create([
        'title' => 'Matching tenant and owner',
        'team_id' => $actor->current_team_id,
        'user_id' => $actor->id,
    ]);

    expect($owned->team_id)->toBe($actor->current_team_id)
        ->and($owned->user_id)->toBe($actor->id);
});

it('rejects foreign tenancy and ownership during direct fill and update', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->createQuietly(['user_id' => $otherOwner->id]);
    $first = Post::withoutGlobalScopes()->create([
        'title' => 'Direct fill target',
        'team_id' => $actor->current_team_id,
        'user_id' => $actor->id,
    ]);
    $second = Post::withoutGlobalScopes()->create([
        'title' => 'Direct update target',
        'team_id' => $actor->current_team_id,
        'user_id' => $actor->id,
    ]);

    expect(function () use ($first, $otherTeam, $otherOwner): void {
        $first->fill([
            'team_id' => $otherTeam->id,
            'user_id' => $otherOwner->id,
        ]);
        $first->save();
    })->toThrow(LogicException::class);

    expect(fn () => $second->update([
        'team_id' => $otherTeam->id,
        'user_id' => $otherOwner->id,
    ]))->toThrow(LogicException::class);

    expect($first->fresh()->team_id)->toBe($actor->current_team_id)
        ->and($first->fresh()->user_id)->toBe($actor->id)
        ->and($second->fresh()->team_id)->toBe($actor->current_team_id)
        ->and($second->fresh()->user_id)->toBe($actor->id);
});

it('supports explicit trusted team creation and movement for infrastructure', function () {
    $actor = createSuperAdmin();
    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->createQuietly(['user_id' => $otherOwner->id]);

    auth()->logout();

    $post = Post::createForTeamForSystem($otherTeam->id, [
        'title' => 'Trusted team post',
        'user_id' => $otherOwner->id,
    ]);
    $role = Role::createForTeamForSystem($otherTeam->id, [
        'name' => 'Trusted team role',
        'slug' => 'trusted-team-role',
        'permissions' => [],
    ]);

    expect($post->team_id)->toBe($otherTeam->id)
        ->and($post->user_id)->toBe($otherOwner->id)
        ->and($role->team_id)->toBe($otherTeam->id);

    expect($post->moveToTeamForSystem($actor->current_team_id, [
        'user_id' => $actor->id,
    ]))->toBeTrue();

    expect($post->refresh()->team_id)->toBe($actor->current_team_id)
        ->and($post->user_id)->toBe($actor->id);

    $this->actingAs($actor);

    $ownerOnly = Post::createForOwnerForSystem($otherOwner->id, [
        'title' => 'Trusted owner post',
        'team_id' => $actor->current_team_id,
    ]);

    expect($ownerOnly->user_id)->toBe($otherOwner->id)
        ->and($ownerOnly->assignOwnerForSystem($actor->id))->toBeTrue()
        ->and($ownerOnly->fresh()->user_id)->toBe($actor->id);
});

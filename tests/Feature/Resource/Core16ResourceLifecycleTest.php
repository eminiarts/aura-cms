<?php

use Aura\Base\Contracts\ResourceLifecycleEvent;
use Aura\Base\Events\ResourceCreated;
use Aura\Base\Events\ResourceDeleted;
use Aura\Base\Events\ResourceDeleting;
use Aura\Base\Events\ResourceForceDeleted;
use Aura\Base\Events\ResourceRestored;
use Aura\Base\Events\ResourceUpdated;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

class Core16LifecycleDocument extends Resource
{
    public static $customTable = true;

    public static array $physicalFields = ['name'];

    public static ?string $slug = 'core16-lifecycle-document';

    public static string $type = 'Core16LifecycleDocument';

    protected $fillable = ['name'];

    protected $table = 'core16_lifecycle_documents';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => Text::class],
        ];
    }
}

final class Core16SoftLifecycleDocument extends Core16LifecycleDocument
{
    use SoftDeletes;

    public static string $type = 'Core16SoftLifecycleDocument';
}

final class Core16GlobalLifecycleDocument extends Resource
{
    public static $customTable = true;

    public static array $physicalFields = ['name'];

    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static ?string $slug = 'core16-global-lifecycle-document';

    public static string $type = 'Core16GlobalLifecycleDocument';

    public static bool $usesMeta = false;

    protected $fillable = ['name'];

    protected $table = 'core16_global_lifecycle_documents';

    public static function getFields(): array
    {
        return [['name' => 'Name', 'slug' => 'name', 'type' => Text::class]];
    }
}

class Core16PostLifecycleAlpha extends Resource
{
    public static ?string $slug = 'core16-post-lifecycle-alpha';

    public static string $type = 'Core16PostLifecycleAlpha';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => Text::class],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => Text::class],
        ];
    }
}

final class Core16PostLifecycleBeta extends Core16PostLifecycleAlpha
{
    public static ?string $slug = 'core16-post-lifecycle-beta';

    public static string $type = 'Core16PostLifecycleBeta';
}

beforeEach(function (): void {
    $this->actingAs($this->actor = createSuperAdmin());

    Schema::create('core16_lifecycle_documents', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('core16_lifecycle_documents');
});

test('create and update events expose immutable queue safe physical and meta changes', function (): void {
    Event::fake([ResourceCreated::class, ResourceUpdated::class]);

    $document = Core16LifecycleDocument::create([
        'name' => 'Original',
        'notes' => 'First note',
    ]);

    Event::assertDispatched(ResourceCreated::class, function (ResourceCreated $event) use ($document): bool {
        $restored = unserialize(serialize($event));

        expect($event)->toBeInstanceOf(ResourceLifecycleEvent::class)
            ->and($event->resourceClass)->toBe(Core16LifecycleDocument::class)
            ->and($event->resourceType)->toBe('Core16LifecycleDocument')
            ->and($event->resourceMorphType)->toBe(Core16LifecycleDocument::class)
            ->and($event->resourceId)->toBe($document->getKey())
            ->and($event->teamId)->toBe(config('aura.teams') ? $this->actor->current_team_id : null)
            ->and($event->ownerId)->toBe($this->actor->getKey())
            ->and($event->physicalChanges['name'])->toBe(['old' => null, 'new' => 'Original'])
            ->and($event->metaChanges['notes'])->toBe(['old' => null, 'new' => 'First note'])
            ->and($restored)->toEqual($event);

        return true;
    });

    $document->name = 'Changed';
    $document->notes = 'Second note';
    $document->save();

    Event::assertDispatched(ResourceUpdated::class, function (ResourceUpdated $event): bool {
        expect($event->physicalChanges['name'])->toBe(['old' => 'Original', 'new' => 'Changed'])
            ->and($event->metaChanges['notes'])->toBe(['old' => 'First note', 'new' => 'Second note']);

        return true;
    });
});

test('no-op saves and without model events do not dispatch lifecycle events', function (): void {
    $document = Core16LifecycleDocument::create(['name' => 'Same', 'notes' => 'Same meta']);
    Event::fake([ResourceCreated::class, ResourceUpdated::class]);

    $document->save();
    Core16LifecycleDocument::withoutEvents(function () use ($document): void {
        $document->name = 'Quiet';
        $document->save();
    });

    Event::assertNotDispatched(ResourceCreated::class);
    Event::assertNotDispatched(ResourceUpdated::class);
});

test('hard delete dispatches stable operation events and cleans only exact Aura dependents', function (): void {
    $document = Core16LifecycleDocument::create(['name' => 'Delete', 'notes' => 'Dependent meta']);
    $other = Core16LifecycleDocument::create(['name' => 'Other', 'notes' => 'Keep']);

    DB::table('meta')->insert([
        'metable_type' => 'Unrelated\\Resource',
        'metable_id' => $document->getKey(),
        'key' => 'keep',
        'value' => 'same id, other type',
    ]);
    DB::table('post_relations')->insert([
        [
            'resource_type' => $document->getMorphClass(),
            'resource_id' => $document->getKey(),
            'related_type' => $other->getMorphClass(),
            'related_id' => $other->getKey(),
            'slug' => 'outgoing',
        ],
        [
            'resource_type' => $other->getMorphClass(),
            'resource_id' => $other->getKey(),
            'related_type' => $document->getMorphClass(),
            'related_id' => $document->getKey(),
            'slug' => 'incoming',
        ],
        [
            'resource_type' => 'Unrelated\\Resource',
            'resource_id' => $document->getKey(),
            'related_type' => 'Unrelated\\Resource',
            'related_id' => $document->getKey(),
            'slug' => 'keep',
        ],
    ]);

    Event::fake([ResourceDeleting::class, ResourceDeleted::class]);
    expect($document->delete())->toBeTrue();

    $deleting = Event::dispatched(ResourceDeleting::class)->sole()[0];
    $deleted = Event::dispatched(ResourceDeleted::class)->sole()[0];

    expect($deleting->operationId)->toBe($deleted->operationId)
        ->and($deleting->eventId)->not->toBe($deleted->eventId)
        ->and($deleted->physicalChanges['name'])->toBe(['old' => 'Delete', 'new' => null])
        ->and($deleted->metaChanges['notes'])->toBe(['old' => 'Dependent meta', 'new' => null])
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(0)
        ->and(DB::table('meta')->where('metable_type', 'Unrelated\\Resource')->where('metable_id', $document->getKey())->count())->toBe(1)
        ->and(DB::table('post_relations')->whereIn('slug', ['outgoing', 'incoming'])->count())->toBe(0)
        ->and(DB::table('post_relations')->where('slug', 'keep')->count())->toBe(1);
});

test('soft delete retains dependents then restore and force delete expose lifecycle events', function (): void {
    $document = Core16SoftLifecycleDocument::create(['name' => 'Soft', 'notes' => 'Retained']);
    Event::fake([
        ResourceDeleting::class,
        ResourceDeleted::class,
        ResourceRestored::class,
        ResourceForceDeleted::class,
        ResourceUpdated::class,
    ]);

    expect($document->delete())->toBeTrue()
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(1);

    expect($document->restore())->toBeTrue();
    Event::assertDispatchedTimes(ResourceRestored::class, 1);
    Event::assertNotDispatched(ResourceUpdated::class);

    expect($document->forceDelete())->toBeTrue()
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(0);

    Event::assertDispatchedTimes(ResourceForceDeleted::class, 1);
});

test('native deleting veto prevents cleanup and lifecycle dispatch', function (): void {
    $document = Core16LifecycleDocument::create(['name' => 'Veto', 'notes' => 'Keep']);
    Core16LifecycleDocument::deleting(fn (): bool => false);
    Event::fake([ResourceDeleting::class, ResourceDeleted::class]);

    expect($document->delete())->toBeFalse()
        ->and(Core16LifecycleDocument::withoutGlobalScopes()->find($document->getKey()))->not->toBeNull()
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(1);

    Event::assertNotDispatched(ResourceDeleting::class);
    Event::assertNotDispatched(ResourceDeleted::class);

    Event::fake([ResourceUpdated::class]);
    $document->name = 'Saved after veto';
    expect($document->save())->toBeTrue();
    Event::assertDispatchedTimes(ResourceUpdated::class, 1);
});

test('quiet hard deletes suppress lifecycle events but still clean exact dependents', function (): void {
    $document = Core16LifecycleDocument::create(['name' => 'Quiet delete', 'notes' => 'Cleanup']);
    Event::fake([ResourceDeleting::class, ResourceDeleted::class]);

    expect($document->deleteQuietly())->toBeTrue()
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(0);

    Event::assertNotDispatched(ResourceDeleting::class);
    Event::assertNotDispatched(ResourceDeleted::class);
});

test('post STI lifecycle identity and cleanup use the exact morph type', function (): void {
    Event::fake([ResourceCreated::class, ResourceDeleting::class, ResourceDeleted::class]);
    $alpha = Core16PostLifecycleAlpha::create(['title' => 'Alpha', 'notes' => 'Alpha meta']);

    DB::table('meta')->insert([
        'metable_type' => Core16PostLifecycleBeta::class,
        'metable_id' => $alpha->getKey(),
        'key' => 'notes',
        'value' => 'Beta meta with the same id',
    ]);

    $alpha->delete();

    Event::assertDispatched(ResourceCreated::class, fn (ResourceCreated $event): bool => $event->resourceType === 'Core16PostLifecycleAlpha'
        && $event->resourceMorphType === Core16PostLifecycleAlpha::class);

    expect(DB::table('meta')->where('metable_type', Core16PostLifecycleAlpha::class)->where('metable_id', $alpha->getKey())->count())->toBe(0)
        ->and(DB::table('meta')->where('metable_type', Core16PostLifecycleBeta::class)->where('metable_id', $alpha->getKey())->count())->toBe(1);
});

test('a synchronous deleted listener failure rolls back the row and dependent cleanup', function (): void {
    $document = Core16LifecycleDocument::create(['name' => 'Rollback delete', 'notes' => 'Still present']);
    Core16LifecycleDocument::deleted(function (): never {
        throw new RuntimeException('legacy deleted listener failed');
    });

    expect(fn (): ?bool => $document->delete())->toThrow(RuntimeException::class, 'legacy deleted listener failed')
        ->and(Core16LifecycleDocument::withoutGlobalScopes()->whereKey($document->getKey())->exists())->toBeTrue()
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(1);
});

test('after commit listeners are suppressed by rollback and fail after durable commit', function (): void {
    $databasePath = tempnam(sys_get_temp_dir(), 'aura-core16-');
    expect($databasePath)->toBeString();

    config()->set('database.connections.core16_lifecycle', [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    DB::purge('core16_lifecycle');
    $connection = DB::connection('core16_lifecycle');
    $testTransactionManager = app('db.transactions');
    $transactionManager = new DatabaseTransactionsManager;
    app()->instance('db.transactions', $transactionManager);
    $connection->setTransactionManager($transactionManager);
    $connection->getSchemaBuilder()->create('core16_global_lifecycle_documents', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
    Auth::logout();

    $observed = 0;
    Event::listen(ResourceCreated::class, function () use (&$observed): void {
        $observed++;
    });

    $connection->beginTransaction();
    $rolledBack = new Core16GlobalLifecycleDocument(['name' => 'Rollback']);
    $rolledBack->setConnection('core16_lifecycle');
    expect($rolledBack->save())->toBeTrue();
    $connection->rollBack();

    expect($observed)->toBe(0)
        ->and($connection->table('core16_global_lifecycle_documents')->count())->toBe(0);

    Event::forget(ResourceCreated::class);
    Event::listen(ResourceCreated::class, function (): never {
        throw new RuntimeException('listener failed after commit');
    });

    $committed = new Core16GlobalLifecycleDocument(['name' => 'Committed']);
    $committed->setConnection('core16_lifecycle');

    expect(fn (): bool => $committed->save())->toThrow(RuntimeException::class, 'listener failed after commit')
        ->and($connection->table('core16_global_lifecycle_documents')->where('name', 'Committed')->exists())->toBeTrue();

    Event::forget(ResourceCreated::class);
    DB::purge('core16_lifecycle');
    app()->instance('db.transactions', $testTransactionManager);
    unlink($databasePath);
});

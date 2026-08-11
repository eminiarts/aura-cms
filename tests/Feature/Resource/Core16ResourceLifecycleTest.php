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
use Aura\Base\ResourceLifecycle\ResourceLifecycleDispatcher;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
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

class Core16SoftLifecycleDocument extends Core16LifecycleDocument
{
    use SoftDeletes;

    public static string $type = 'Core16SoftLifecycleDocument';
}

final class Core16TeamLifecycleDocument extends Core16LifecycleDocument
{
    public static string $scopeMode = self::SCOPE_TEAM;

    public static string $type = 'Core16TeamLifecycleDocument';
}

final class Core16RestoreVetoLifecycleDocument extends Core16SoftLifecycleDocument
{
    public static string $type = 'Core16RestoreVetoLifecycleDocument';
}

final class Core16RestoreExceptionLifecycleDocument extends Core16SoftLifecycleDocument
{
    public static string $type = 'Core16RestoreExceptionLifecycleDocument';
}

final class Core16RestoreSavingVetoLifecycleDocument extends Core16SoftLifecycleDocument
{
    public static string $type = 'Core16RestoreSavingVetoLifecycleDocument';
}

class Core16SharedLifecycleDocument extends Core16LifecycleDocument
{
    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static bool $sharedAcrossTeams = true;

    public static string $type = 'Core16SharedLifecycleDocument';
}

class Core16GlobalLifecycleDocument extends Resource
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

class Core16GlobalSoftLifecycleDocument extends Core16GlobalLifecycleDocument
{
    use SoftDeletes;

    public static string $type = 'Core16GlobalSoftLifecycleDocument';
}

final class Core16SavingAbortLifecycleDocument extends Core16LifecycleDocument
{
    public static string $type = 'Core16SavingAbortLifecycleDocument';
}

final class Core16PrivilegedAbortLifecycleDocument extends Core16LifecycleDocument
{
    public static string $type = 'Core16PrivilegedAbortLifecycleDocument';
}

final class Core16GlobalAbortLifecycleDocument extends Core16LifecycleDocument
{
    public static string $scopeMode = self::SCOPE_TEAM;

    public static bool $sharedAcrossTeams = true;

    public static string $type = 'Core16GlobalAbortLifecycleDocument';
}

final class Core16RestoreSwapLifecycleDocument extends Core16GlobalSoftLifecycleDocument
{
    public static string $type = 'Core16RestoreSwapLifecycleDocument';
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

    $this->travel(2)->minutes();
    $document->save();
    $document->setUpdatedAt(now());
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
    $related = Core16LifecycleDocument::create(['name' => 'Related']);
    DB::table('post_relations')->insert([
        'resource_type' => $document->getMorphClass(),
        'resource_id' => $document->getKey(),
        'related_type' => $related->getMorphClass(),
        'related_id' => $related->getKey(),
        'slug' => 'soft-retained',
    ]);
    Event::fake([
        ResourceDeleting::class,
        ResourceDeleted::class,
        ResourceRestored::class,
        ResourceForceDeleted::class,
        ResourceUpdated::class,
    ]);

    expect($document->delete())->toBeTrue()
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(1)
        ->and(DB::table('post_relations')->where('slug', 'soft-retained')->count())->toBe(1);

    $softDeleted = Event::dispatched(ResourceDeleted::class)->sole()[0];
    expect($softDeleted->physicalChanges)->toHaveKey('deleted_at')
        ->and($softDeleted->physicalChanges)->not->toHaveKey('name')
        ->and($softDeleted->metaChanges)->toBe([]);

    expect($document->restore())->toBeTrue();
    Event::assertDispatchedTimes(ResourceRestored::class, 1);
    Event::assertNotDispatched(ResourceUpdated::class);

    expect($document->forceDelete())->toBeTrue()
        ->and(DB::table('meta')->where('metable_type', $document->getMorphClass())->where('metable_id', $document->getKey())->count())->toBe(0)
        ->and(DB::table('post_relations')->where('slug', 'soft-retained')->count())->toBe(0);

    Event::assertDispatchedTimes(ResourceForceDeleted::class, 1);
});

test('force delete completes its native and typed terminal lifecycle when a typed listener fails', function (): void {
    $document = Core16SoftLifecycleDocument::create(['name' => 'Force listener failure', 'notes' => 'Cleanup']);
    $observed = [];
    Exceptions::fake();

    Event::listen(ResourceDeleting::class, function () use (&$observed): never {
        $observed[] = ResourceDeleting::class;

        throw new RuntimeException('typed deleting listener failed');
    });
    Event::listen(ResourceDeleted::class, function () use (&$observed): void {
        $observed[] = ResourceDeleted::class;
    });
    Event::listen(ResourceForceDeleted::class, function () use (&$observed): void {
        $observed[] = ResourceForceDeleted::class;
    });
    Core16SoftLifecycleDocument::forceDeleted(function () use (&$observed): void {
        $observed[] = 'eloquent.forceDeleted';
    });

    expect($document->forceDelete())->toBeTrue()
        ->and($document->isForceDeleting())->toBeFalse()
        ->and($observed)->toBe([
            ResourceDeleting::class,
            ResourceDeleted::class,
            ResourceForceDeleted::class,
            'eloquent.forceDeleted',
        ])
        ->and(Core16SoftLifecycleDocument::withTrashed()->whereKey($document->getKey())->exists())->toBeFalse();

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'typed deleting listener failed');
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

test('restore vetoes and listener exceptions discard pending lifecycle state', function (): void {
    Event::fake([ResourceUpdated::class]);

    $vetoed = Core16RestoreVetoLifecycleDocument::create(['name' => 'Veto restore']);
    $vetoed->delete();
    Core16RestoreVetoLifecycleDocument::restoring(fn (): false => false);

    expect($vetoed->restore())->toBeFalse();
    $vetoed->name = 'Updated after veto';
    expect($vetoed->save())->toBeTrue();

    $exception = Core16RestoreExceptionLifecycleDocument::create(['name' => 'Exception restore']);
    $exception->delete();
    Core16RestoreExceptionLifecycleDocument::restoring(function (): never {
        throw new RuntimeException('restoring listener failed');
    });

    expect(fn (): bool => $exception->restore())
        ->toThrow(RuntimeException::class, 'restoring listener failed');
    $exception->name = 'Updated after exception';
    expect($exception->save())->toBeTrue();

    Event::assertDispatchedTimes(ResourceUpdated::class, 2);
});

test('saving vetoes and exceptions discard normal and privileged lifecycle state', function (): void {
    Event::fake([ResourceUpdated::class]);

    $normal = Core16SavingAbortLifecycleDocument::create(['name' => 'Normal abort']);
    $normalMode = 'veto';
    Core16SavingAbortLifecycleDocument::saving(function () use (&$normalMode): bool {
        if ($normalMode === 'exception') {
            throw new RuntimeException('normal saving listener failed');
        }

        return $normalMode !== 'veto';
    });

    $normal->name = 'Normal vetoed';
    expect($normal->save())->toBeFalse();
    $normalMode = 'pass';
    $normal->name = 'Normal retry';
    expect($normal->save())->toBeTrue();
    $normalMode = 'exception';
    $normal->name = 'Normal exception';
    expect(fn (): bool => $normal->save())
        ->toThrow(RuntimeException::class, 'normal saving listener failed');
    $normalMode = 'pass';
    $normal->name = 'Normal exception retry';
    expect($normal->save())->toBeTrue();

    $privileged = Core16PrivilegedAbortLifecycleDocument::create(['name' => 'Privileged abort']);
    $privilegedMode = 'veto';
    Core16PrivilegedAbortLifecycleDocument::saving(function () use (&$privilegedMode): bool {
        if ($privilegedMode === 'exception') {
            throw new RuntimeException('privileged saving listener failed');
        }

        return $privilegedMode !== 'veto';
    });
    $saveSystemResource = new ReflectionMethod(Resource::class, 'saveSystemResource');

    $privileged->name = 'Privileged vetoed';
    expect($saveSystemResource->invoke($privileged))->toBeFalse();
    $privilegedMode = 'pass';
    $privileged->name = 'Privileged retry';
    expect($saveSystemResource->invoke($privileged))->toBeTrue();
    $privilegedMode = 'exception';
    $privileged->name = 'Privileged exception';
    expect(fn (): mixed => $saveSystemResource->invoke($privileged))
        ->toThrow(RuntimeException::class, 'privileged saving listener failed');
    $privilegedMode = 'pass';
    $privileged->name = 'Privileged exception retry';
    expect($saveSystemResource->invoke($privileged))->toBeTrue();

    if (config('aura.teams')) {
        $global = Core16GlobalAbortLifecycleDocument::create(['name' => 'Global abort']);
        $globalMode = 'veto';
        Core16GlobalAbortLifecycleDocument::saving(function () use (&$globalMode): bool {
            if ($globalMode === 'exception') {
                throw new RuntimeException('global saving listener failed');
            }

            return $globalMode !== 'veto';
        });
        $saveGlobalResource = new ReflectionMethod(Resource::class, 'saveGlobalResource');

        $global->name = 'Global vetoed';
        expect($saveGlobalResource->invoke($global))->toBeFalse();
        $globalMode = 'pass';
        $global->name = 'Global retry';
        expect($saveGlobalResource->invoke($global))->toBeTrue();
        $globalMode = 'exception';
        $global->name = 'Global exception';
        expect(fn (): mixed => $saveGlobalResource->invoke($global))
            ->toThrow(RuntimeException::class, 'global saving listener failed');
        $globalMode = 'pass';
        $global->name = 'Global exception retry';
        expect($saveGlobalResource->invoke($global))->toBeTrue();
    }

    Event::assertDispatchedTimes(ResourceUpdated::class, config('aura.teams') ? 6 : 4);
});

test('a saving veto during restore is discarded across supported SoftDeletes behavior', function (): void {
    Event::fake([ResourceRestored::class, ResourceUpdated::class]);

    $document = Core16RestoreSavingVetoLifecycleDocument::create(['name' => 'Restore save veto']);
    $document->delete();
    $vetoNextSave = true;
    Core16RestoreSavingVetoLifecycleDocument::saving(function () use (&$vetoNextSave): bool {
        if (! $vetoNextSave) {
            return true;
        }

        $vetoNextSave = false;

        return false;
    });

    expect($document->restore())->toBeFalse();
    Event::assertNotDispatched(ResourceRestored::class);

    $document->name = 'Updated after restore save veto';
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

test('dispatcher state is bound to one resource operation and connection', function (): void {
    $first = Core16LifecycleDocument::create(['name' => 'First']);
    $second = Core16LifecycleDocument::create(['name' => 'Second']);
    $dispatcher = app(ResourceLifecycleDispatcher::class);
    $state = $dispatcher->beginDelete($first);

    expect(fn (): mixed => $dispatcher->dispatchDeleted($second, $state))
        ->toThrow(LogicException::class, 'does not belong to the supplied resource');

    expect(fn (): mixed => $dispatcher->dispatchDeleted($first, $state))
        ->toThrow(LogicException::class, 'hard-delete resource lifecycle operation has not completed');

    $first->exists = false;
    expect(fn (): mixed => $dispatcher->dispatchDeleted($first, $state))
        ->toThrow(LogicException::class, 'hard-delete resource lifecycle operation has not completed');
    $first->exists = true;

    $saveState = $dispatcher->beginSave($first);
    expect(fn (): mixed => $dispatcher->dispatchForceDeleted($first, $saveState))
        ->toThrow(LogicException::class, 'cannot be used for');

    $first->name = 'Not persisted';
    $prematureSaveState = $dispatcher->beginSave($first);
    expect(fn (): mixed => $dispatcher->dispatchSaved($first, $prematureSaveState))
        ->toThrow(LogicException::class, 'update operation has not completed');

    $first->refresh();
    $cleanSaveState = $dispatcher->beginSave($first);
    expect(fn (): mixed => $dispatcher->dispatchSaved($first, $cleanSaveState))
        ->toThrow(LogicException::class, 'update operation has not completed');

    $first->updated_at = $first->updated_at?->addSecond();
    $timestampSaveState = $dispatcher->beginSave($first);
    expect(fn (): mixed => $dispatcher->dispatchSaved($first, $timestampSaveState))
        ->toThrow(LogicException::class, 'update operation has not completed');

    $first->refresh();
    $first->setAttribute('fields', ['notes' => 'Pending note']);
    $pendingMetaState = $dispatcher->beginSave($first);
    expect(fn (): mixed => $dispatcher->dispatchSaved($first, $pendingMetaState))
        ->toThrow(LogicException::class, 'update operation has not completed');

    $soft = Core16SoftLifecycleDocument::create(['name' => 'Restore operation']);
    $prematureSoftDeleteState = $dispatcher->beginDelete($soft);
    $soft->deleted_at = now();
    expect(fn (): mixed => $dispatcher->dispatchDeleted($soft, $prematureSoftDeleteState))
        ->toThrow(LogicException::class, 'soft-delete resource lifecycle operation has not completed');

    $soft->refresh();
    $soft->deleteQuietly();
    $restoreState = $dispatcher->beginRestore($soft);
    expect(fn (): mixed => $dispatcher->dispatchSaved($soft, $restoreState))
        ->toThrow(LogicException::class, 'cannot be used for');

    $soft->deleted_at = null;
    expect(fn (): mixed => $dispatcher->dispatchRestored($soft, $restoreState))
        ->toThrow(LogicException::class, 'restore resource lifecycle operation has not completed');

    $first->refresh();
    $first->name = 'Explicit seam update';
    $explicitSaveState = $dispatcher->beginSave($first);
    $clonedSaveState = clone $explicitSaveState;
    expect(fn (): string => serialize($explicitSaveState))
        ->toThrow(LogicException::class, 'cannot be serialized');

    (new ReflectionProperty(Resource::class, 'resourceLifecycleState'))->setValue($first, $explicitSaveState);
    Event::fake([ResourceUpdated::class]);
    $first->save();
    Event::assertDispatchedTimes(ResourceUpdated::class, 1);

    expect(fn (): mixed => $dispatcher->dispatchSaved($first, $clonedSaveState))
        ->toThrow(LogicException::class, 'no longer active');
});

test('deletion events use persisted ownership context and resolved connection identity', function (): void {
    $document = Core16LifecycleDocument::create(['name' => 'Stored context']);
    $persistedTeamId = $document->getRawOriginal('team_id');
    $persistedOwnerId = $document->getRawOriginal('user_id');
    $document->setAttribute('team_id', 999999);
    $document->setAttribute('user_id', 999999);
    Event::fake([ResourceDeleting::class, ResourceDeleted::class]);

    $document->delete();
    $event = Event::dispatched(ResourceDeleted::class)->sole()[0];

    expect($event->teamId)->toBe($persistedTeamId)
        ->and($event->ownerId)->toBe($persistedOwnerId)
        ->and($event->connectionName)->toBe(DB::connection()->getName())
        ->and($event->connectionIdentity)->toBeString()->not->toBeEmpty();
});

test('team and global lifecycle payloads expose only their declared ownership context', function (): void {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team ownership context is only available with teams enabled.');
    }

    Event::fake([ResourceCreated::class]);
    $team = Core16TeamLifecycleDocument::createForTeamForSystem(
        $this->actor->current_team_id,
        ['name' => 'Team context'],
    );
    $global = Core16SharedLifecycleDocument::create(['name' => 'Global context']);

    $teamEvent = Event::dispatched(ResourceCreated::class)
        ->first(fn (array $payload): bool => $payload[0]->resourceId === $team->getKey()
            && $payload[0]->resourceClass === Core16TeamLifecycleDocument::class)[0];
    $globalEvent = Event::dispatched(ResourceCreated::class)
        ->first(fn (array $payload): bool => $payload[0]->resourceId === $global->getKey()
            && $payload[0]->resourceClass === Core16SharedLifecycleDocument::class)[0];

    expect($teamEvent->scopeMode)->toBe(Resource::SCOPE_TEAM)
        ->and($teamEvent->teamId)->toBe($this->actor->current_team_id)
        ->and($teamEvent->ownerId)->toBeNull()
        ->and($globalEvent->scopeMode)->toBe(Resource::SCOPE_GLOBAL)
        ->and($globalEvent->teamId)->toBeNull()
        ->and($globalEvent->ownerId)->toBeNull()
        ->and($globalEvent->sharedAcrossTeams)->toBeTrue();
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

test('restore and force delete lifecycle verification reads from the writer', function (): void {
    $writerPath = tempnam(sys_get_temp_dir(), 'aura-core16-writer-');
    $readerPath = tempnam(sys_get_temp_dir(), 'aura-core16-reader-');
    expect($writerPath)->toBeString()
        ->and($readerPath)->toBeString();

    config()->set('database.connections.core16_split_lifecycle', [
        'driver' => 'sqlite',
        'database' => $writerPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
        'sticky' => false,
    ]);
    DB::purge('core16_split_lifecycle');
    $connection = DB::connection('core16_split_lifecycle');
    $connection->getSchemaBuilder()->create('core16_global_lifecycle_documents', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    $reader = new PDO('sqlite:'.$readerPath);
    $reader->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $reader->exec(<<<'SQL'
        CREATE TABLE core16_global_lifecycle_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        )
        SQL);
    $connection->setReadPdo($reader);
    Auth::logout();

    $document = new Core16GlobalSoftLifecycleDocument(['name' => 'Writer lifecycle']);
    $document->setConnection('core16_split_lifecycle');
    expect($document->save())->toBeTrue()
        ->and($document->delete())->toBeTrue();

    $writerRow = $connection->table('core16_global_lifecycle_documents')
        ->useWritePdo()
        ->where('id', $document->getKey())
        ->first();
    expect($writerRow)->not->toBeNull();

    $insert = $reader->prepare(<<<'SQL'
        INSERT INTO core16_global_lifecycle_documents
            (id, name, created_at, updated_at, deleted_at)
        VALUES
            (:id, :name, :created_at, :updated_at, :deleted_at)
        SQL);
    $insert->execute([
        'id' => $writerRow->id,
        'name' => $writerRow->name,
        'created_at' => $writerRow->created_at,
        'updated_at' => $writerRow->updated_at,
        'deleted_at' => $writerRow->deleted_at,
    ]);

    Event::fake([
        ResourceDeleting::class,
        ResourceDeleted::class,
        ResourceRestored::class,
        ResourceForceDeleted::class,
    ]);

    expect($document->restore())->toBeTrue()
        ->and($connection->table('core16_global_lifecycle_documents')->where('id', $document->getKey())->value('deleted_at'))->not->toBeNull()
        ->and($connection->table('core16_global_lifecycle_documents')->useWritePdo()->where('id', $document->getKey())->value('deleted_at'))->toBeNull()
        ->and($document->forceDelete())->toBeTrue()
        ->and($connection->table('core16_global_lifecycle_documents')->where('id', $document->getKey())->exists())->toBeTrue()
        ->and($connection->table('core16_global_lifecycle_documents')->useWritePdo()->where('id', $document->getKey())->exists())->toBeFalse();

    Event::assertDispatchedTimes(ResourceRestored::class, 1);
    Event::assertDispatchedTimes(ResourceForceDeleted::class, 1);

    DB::purge('core16_split_lifecycle');
    unlink($writerPath);
    unlink($readerPath);
});

test('restore rejects connection and table swaps before writing', function (): void {
    if (config('aura.teams')) {
        $this->markTestSkipped('The two-writer restoration boundary is exercised with teams disabled.');
    }

    $firstPath = tempnam(sys_get_temp_dir(), 'aura-core16-first-');
    $secondPath = tempnam(sys_get_temp_dir(), 'aura-core16-second-');
    expect($firstPath)->toBeString()
        ->and($secondPath)->toBeString();

    foreach (['core16_restore_first' => $firstPath, 'core16_restore_second' => $secondPath] as $name => $path) {
        config()->set("database.connections.{$name}", [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge($name);
    }

    DB::connection('core16_restore_first')->getSchemaBuilder()
        ->create('core16_global_lifecycle_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    DB::connection('core16_restore_second')->getSchemaBuilder()
        ->create('core16_swapped_lifecycle_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    Auth::logout();

    $document = new Core16RestoreSwapLifecycleDocument(['name' => 'Original writer']);
    $document->setConnection('core16_restore_first');
    expect($document->save())->toBeTrue()
        ->and($document->delete())->toBeTrue();

    Core16RestoreSwapLifecycleDocument::restoring(function (Core16RestoreSwapLifecycleDocument $resource): void {
        $resource->setConnection('core16_restore_second');
        $resource->setTable('core16_swapped_lifecycle_documents');
    });

    expect(fn (): bool => $document->restore())
        ->toThrow(LogicException::class, 'cannot change during restoration')
        ->and(DB::connection('core16_restore_first')
            ->table('core16_global_lifecycle_documents')
            ->useWritePdo()
            ->where('id', $document->getKey())
            ->whereNotNull('deleted_at')
            ->exists())->toBeTrue()
        ->and(DB::connection('core16_restore_second')
            ->table('core16_swapped_lifecycle_documents')
            ->useWritePdo()
            ->count())->toBe(0);

    DB::purge('core16_restore_first');
    DB::purge('core16_restore_second');
    unlink($firstPath);
    unlink($secondPath);
});

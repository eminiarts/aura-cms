<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

/**
 * Test resource that declares a single legitimate bulk action.
 */
class SecurityBulkModel extends Resource
{
    public array $bulkActions = [
        'deleteSelected' => [
            'label' => 'Delete',
            'ability' => 'delete',
        ],
        'deleteCollectionReceiver' => [
            'label' => 'Delete collection receiver',
            'ability' => 'delete',
            'method' => 'collection',
        ],
        'recordCollectionInvocation' => [
            'label' => 'Record collection invocation',
            'ability' => 'update',
            'method' => 'collection',
        ],
        'customWithoutAbility' => 'Custom without ability',
        'deleteThenFail' => [
            'label' => 'Delete then fail',
            'ability' => 'delete',
        ],
        'incrementInvocation' => [
            'label' => 'Increment invocation',
            'ability' => 'update',
        ],
        'incrementCollectionInvocation' => [
            'label' => 'Increment collection invocation',
            'ability' => 'update',
            'method' => 'collection',
        ],
        'invalidCollectionHandler' => [
            'label' => 'Invalid collection handler',
            'ability' => 'update',
            'method' => 'collection',
        ],
    ];

    public static $singularName = 'SecurityBulk';

    public static ?string $slug = 'securitybulk';

    public static string $type = 'SecurityBulk';

    public function customWithoutAbility(): void
    {
        $this->content = 'custom-action-ran';
        $this->save();
    }

    public function deleteCollectionReceiver(array $ids): void
    {
        $this->delete();
    }

    public function deleteSelected($ids = null)
    {
        // Per-item destructive action invoked by bulkAction().
        $this->delete();
    }

    public function deleteThenFail(): void
    {
        $shouldFail = $this->title === 'Fail second';
        $this->delete();

        if ($shouldFail) {
            throw new RuntimeException('simulated handler failure');
        }
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'searchable' => true,
                'slug' => 'title',
            ],
        ];
    }

    public function incrementCollectionInvocation(array $ids): void
    {
        foreach ($ids as $id) {
            static::query()->whereKey($id)->increment('content');
        }
    }

    public function incrementInvocation(): void
    {
        static::query()->whereKey($this->getKey())->increment('content');
    }

    public function indexQuery($query, $table = null)
    {
        return $query->where('title', '!=', 'Out of scope');
    }

    public function invalidCollectionHandler(): void
    {
        $this->content = 'invalid-handler-ran';
        $this->save();
    }

    public function recordCollectionInvocation(array $ids): void
    {
        self::create(['title' => 'UNAUTHORIZED SIDE EFFECT']);
    }
}

class SecurityBulkModelPolicy
{
    public function delete(User $user, SecurityBulkModel $resource): bool
    {
        return $user->exists && $resource->title === 'Allowed';
    }
}

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([SecurityBulkModel::class]);
    Aura::setModel(new SecurityBulkModel);
    Cache::clear();
});

test('bulkAction rejects an action that is not in the declared allowlist', function () {
    // 'delete' is a real method on the model but is NOT a declared bulk action.
    $this->actingAs(createSuperAdmin());

    SecurityBulkModel::create(['title' => 'Keep me 1']);
    SecurityBulkModel::create(['title' => 'Keep me 2']);

    expect(SecurityBulkModel::count())->toBe(2);

    $model = SecurityBulkModel::first();
    $ids = SecurityBulkModel::pluck('id')->toArray();

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('selected', $ids)
        ->call('bulkAction', 'delete')
        ->assertStatus(403);

    // Arbitrary method invocation blocked: records untouched.
    expect(SecurityBulkModel::count())->toBe(2);
});

test('bulkAction blocks a declared action the user is not authorized for', function () {
    // Limited admin (Editor role) has no delete permission for this resource.
    $this->actingAs(createAdmin());

    SecurityBulkModel::create(['title' => 'Protected 1']);
    SecurityBulkModel::create(['title' => 'Protected 2']);

    expect(SecurityBulkModel::count())->toBe(2);

    $model = SecurityBulkModel::first();
    $ids = SecurityBulkModel::pluck('id')->toArray();

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('selected', $ids)
        ->call('bulkAction', 'deleteSelected')
        ->assertStatus(403);

    // Authorization failed: nothing deleted.
    expect(SecurityBulkModel::count())->toBe(2);
});

test('bulkAction runs a declared action for an authorized user', function () {
    // Control: super admin passes both the allowlist and the policy check.
    $this->actingAs(createSuperAdmin());

    SecurityBulkModel::create(['title' => 'Delete me 1']);
    SecurityBulkModel::create(['title' => 'Delete me 2']);

    expect(SecurityBulkModel::count())->toBe(2);

    $model = SecurityBulkModel::first();
    $ids = SecurityBulkModel::pluck('id')->toArray();

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('selected', $ids)
        ->call('bulkAction', 'deleteSelected')
        ->assertHasNoErrors();

    expect(SecurityBulkModel::count())->toBe(0);
});

test('bulkAction authorizes every selected record before mutating any record', function () {
    $this->actingAs(createSuperAdmin());
    Gate::policy(SecurityBulkModel::class, SecurityBulkModelPolicy::class);

    $denied = SecurityBulkModel::create(['title' => 'Denied']);
    $allowed = SecurityBulkModel::create(['title' => 'Allowed']);

    livewire(Table::class, ['query' => null, 'model' => $allowed])
        ->set('selected', [$allowed->getKey(), $denied->getKey()])
        ->call('bulkAction', 'deleteSelected')
        ->assertStatus(403);

    expect(SecurityBulkModel::whereKey($allowed->getKey())->exists())->toBeTrue()
        ->and(SecurityBulkModel::whereKey($denied->getKey())->exists())->toBeTrue();
});

test('bulk endpoints reject action definitions declared for the other execution mode', function () {
    $this->actingAs(createSuperAdmin());

    $receiver = SecurityBulkModel::create(['title' => 'Receiver']);
    $selected = SecurityBulkModel::create(['title' => 'Selected']);

    livewire(Table::class, ['query' => null, 'model' => $receiver])
        ->set('selected', [$selected->getKey()])
        ->call('bulkCollectionAction', 'deleteSelected')
        ->assertStatus(422);

    livewire(Table::class, ['query' => null, 'model' => $receiver])
        ->set('selected', [$selected->getKey()])
        ->call('bulkAction', 'deleteCollectionReceiver')
        ->assertStatus(422);

    expect(SecurityBulkModel::whereKey($receiver->getKey())->exists())->toBeTrue()
        ->and(SecurityBulkModel::whereKey($selected->getKey())->exists())->toBeTrue();
});

test('bulk collection action rejects an empty explicit selection before invoking its handler', function () {
    $this->actingAs(createSuperAdmin());

    $receiver = SecurityBulkModel::create(['title' => 'Receiver']);

    livewire(Table::class, ['query' => null, 'model' => $receiver])
        ->set('selected', [])
        ->call('bulkCollectionAction', 'recordCollectionInvocation')
        ->assertHasErrors(['selected']);

    expect(SecurityBulkModel::where('title', 'UNAUTHORIZED SIDE EFFECT')->exists())->toBeFalse();
});

test('bulk collection action invokes an authorized selected receiver instead of the mounted decoy', function () {
    $this->actingAs(createAdmin());
    Gate::policy(SecurityBulkModel::class, SecurityBulkModelPolicy::class);

    $deniedReceiver = SecurityBulkModel::create(['title' => 'Denied']);
    $allowedTarget = SecurityBulkModel::create(['title' => 'Allowed']);

    livewire(Table::class, ['query' => null, 'model' => $deniedReceiver])
        ->set('selected', [$allowedTarget->getKey()])
        ->call('bulkCollectionAction', 'deleteCollectionReceiver')
        ->assertHasNoErrors();

    expect(SecurityBulkModel::whereKey($deniedReceiver->getKey())->exists())->toBeTrue()
        ->and(SecurityBulkModel::whereKey($allowedTarget->getKey())->exists())->toBeFalse();
});

test('custom bulk action without an explicit ability fails closed', function () {
    $this->actingAs(createSuperAdmin());

    $resource = SecurityBulkModel::create([
        'title' => 'Custom target',
        'content' => 'unchanged',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'customWithoutAbility')
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('bulk action rejects a mixed valid and missing selection before any mutation', function () {
    $this->actingAs(createSuperAdmin());

    $resource = SecurityBulkModel::create(['title' => 'Keep me']);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->set('selected', [$resource->getKey(), 999999])
        ->call('bulkAction', 'deleteSelected')
        ->assertHasErrors(['selected']);

    expect(SecurityBulkModel::whereKey($resource->getKey())->exists())->toBeTrue();
});

test('bulk action normalizes duplicate selected identifiers and invokes each record once', function () {
    $this->actingAs(createSuperAdmin());

    $resource = SecurityBulkModel::create([
        'title' => 'Invoke once',
        'content' => '0',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->set('selected', [$resource->getKey(), $resource->getKey(), (string) $resource->getKey()])
        ->call('bulkAction', 'incrementInvocation')
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('1');
});

test('bulk action invokes a canonical record once when an effective query returns duplicate rows', function () {
    $this->actingAs(createSuperAdmin());

    $resource = SecurityBulkModel::create([
        'title' => 'Joined bulk target',
        'content' => '0',
    ]);
    $queryHash = DynamicFunctions::add(function (): Builder {
        $duplicates = DB::query()
            ->selectRaw('1 as duplicate_marker')
            ->unionAll(DB::query()->selectRaw('2 as duplicate_marker'));

        return SecurityBulkModel::query()->crossJoinSub($duplicates, 'core05_bulk_duplicates');
    });

    livewire(Table::class, ['query' => $queryHash, 'model' => new SecurityBulkModel])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'incrementInvocation')
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('1');
});

test('bulk collection action receives canonical ids when an effective query returns duplicate rows', function () {
    $this->actingAs(createSuperAdmin());

    $resource = SecurityBulkModel::create([
        'title' => 'Joined collection target',
        'content' => '0',
    ]);
    $queryHash = DynamicFunctions::add(function (): Builder {
        $duplicates = DB::query()
            ->selectRaw('1 as duplicate_marker')
            ->unionAll(DB::query()->selectRaw('2 as duplicate_marker'));

        return SecurityBulkModel::query()->crossJoinSub($duplicates, 'core05_collection_duplicates');
    });

    livewire(Table::class, ['query' => $queryHash, 'model' => new SecurityBulkModel])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'incrementCollectionInvocation')
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('1');
});

test('bulk action rolls back earlier database writes when a later handler throws', function () {
    $this->actingAs(createSuperAdmin());

    $failSecond = SecurityBulkModel::create(['title' => 'Fail second']);
    $succeedFirst = SecurityBulkModel::create(['title' => 'Succeed first']);

    expect(fn () => livewire(Table::class, ['query' => null, 'model' => $succeedFirst])
        ->set('selected', [$succeedFirst->getKey(), $failSecond->getKey()])
        ->call('bulkAction', 'deleteThenFail'))
        ->toThrow(RuntimeException::class, 'simulated handler failure');

    expect(SecurityBulkModel::whereKey($succeedFirst->getKey())->exists())->toBeTrue()
        ->and(SecurityBulkModel::whereKey($failSecond->getKey())->exists())->toBeTrue();
});

test('bulk action rejects a mixed in-scope and out-of-scope selection atomically', function () {
    $this->actingAs(createSuperAdmin());

    $inScope = SecurityBulkModel::create(['title' => 'In scope']);
    $outOfScope = SecurityBulkModel::create(['title' => 'Out of scope']);

    livewire(Table::class, ['query' => null, 'model' => new SecurityBulkModel])
        ->set('selected', [$inScope->getKey(), $outOfScope->getKey()])
        ->call('bulkAction', 'deleteSelected')
        ->assertHasErrors(['selected']);

    expect(SecurityBulkModel::whereKey($inScope->getKey())->exists())->toBeTrue()
        ->and(SecurityBulkModel::whereKey($outOfScope->getKey())->exists())->toBeTrue();
});

test('bulk select-all rejects an effective empty scope before invoking a handler', function () {
    $this->actingAs(createSuperAdmin());

    $outOfScope = SecurityBulkModel::create(['title' => 'Out of scope']);

    livewire(Table::class, ['query' => null, 'model' => new SecurityBulkModel])
        ->set('selectAll', true)
        ->call('bulkAction', 'deleteSelected')
        ->assertHasErrors(['selected']);

    expect(SecurityBulkModel::whereKey($outOfScope->getKey())->exists())->toBeTrue();
});

test('collection handler signature is preflighted before any invocation', function () {
    $this->actingAs(createSuperAdmin());

    $resource = SecurityBulkModel::create([
        'title' => 'Signature target',
        'content' => 'unchanged',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'invalidCollectionHandler')
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

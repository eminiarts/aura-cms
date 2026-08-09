<?php

use Aura\Base\BaseResource;
use Aura\Base\Facades\Aura;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Fields\HasMany;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Livewire\livewire;

class Core05MutationResource extends Resource
{
    public array $actions = [
        'captureAuthoritativeAttributes' => [
            'label' => 'Capture authoritative attributes',
            'ability' => 'update',
        ],
        'deleteRecord' => [
            'label' => 'Delete',
            'ability' => 'delete',
        ],
        'hiddenAction' => [
            'label' => 'Hidden',
            'ability' => 'update',
            'conditional_logic' => [Core05MutationResource::class, 'hideAction'],
        ],
        'missingAction' => [
            'label' => 'Missing',
            'ability' => 'update',
        ],
        'parameterizedAction' => [
            'label' => 'Parameterized',
            'ability' => 'update',
        ],
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
        'incrementInvocation' => [
            'label' => 'Increment invocation',
            'ability' => 'update',
        ],
        'customWithoutAbility' => [
            'label' => 'Custom without ability',
        ],
    ];

    public array $bulkActions = [
        'captureAuthoritativeAttributes' => [
            'label' => 'Capture authoritative attributes',
            'ability' => 'update',
        ],
        'captureCollectionAttributes' => [
            'label' => 'Capture collection attributes',
            'ability' => 'update',
            'method' => 'collection',
        ],
        'markBulkReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public static ?string $slug = 'core05-mutation';

    public static string $type = 'Core05Mutation';

    public static int $updateInvocations = 0;

    public static bool $useCollidingIndexQuery = false;

    public function captureAuthoritativeAttributes(): void
    {
        $this->content = json_encode($this->mutationAttributeSnapshot(), JSON_THROW_ON_ERROR);
        $this->save();
    }

    public function captureCollectionAttributes(array $ids): void
    {
        $snapshot = $this->mutationAttributeSnapshot();
        $snapshot['ids'] = $ids;

        $this->content = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $this->save();
    }

    public function customWithoutAbility(): void
    {
        $this->content = 'custom-action-ran';
        $this->save();
    }

    public function deleteRecord(): void
    {
        $this->delete();
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Status',
                'options' => [
                    [
                        'key' => 'draft',
                        'value' => 'Draft',
                        'color' => 'gray',
                    ],
                    [
                        'key' => 'reviewed',
                        'value' => 'Reviewed',
                        'color' => 'green',
                    ],
                ],
            ],
        ];
    }

    public function hiddenAction(): void
    {
        $this->content = 'hidden-action-ran';
        $this->save();
    }

    public static function hideAction(): bool
    {
        return false;
    }

    public function incrementInvocation(): void
    {
        static::query()->whereKey($this->getKey())->increment('content');
    }

    public function indexQuery(Builder $query, ?Table $table = null): Builder
    {
        $query->where($query->getModel()->qualifyColumn('title'), '!=', 'Excluded by indexQuery');

        if (static::$useCollidingIndexQuery) {
            $query
                ->join(
                    'core05_mutation_collisions',
                    'core05_mutation_collisions.base_id',
                    '=',
                    $query->getModel()->qualifyColumn('id'),
                )
                ->select('*');
        }

        return $query;
    }

    public function kanbanQuery($query)
    {
        return $query->where($query->getModel()->qualifyColumn('title'), '!=', 'Excluded by kanbanQuery');
    }

    public function markBulkReviewed(): void
    {
        $this->content = 'reviewed-by-bulk-action';
        $this->save();
    }

    public function markReviewed(): void
    {
        $this->content = 'reviewed-by-action';
        $this->save();
    }

    public function parameterizedAction(string $content): void
    {
        $this->content = $content;
        $this->save();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::updating(function (): void {
            static::$updateInvocations++;
        });
    }

    /**
     * @return array{id: mixed, user_id: mixed, team_id: mixed, title: mixed, content: mixed, data: mixed, status: mixed}
     */
    private function mutationAttributeSnapshot(): array
    {
        return [
            'id' => $this->getKey(),
            'user_id' => $this->getAttribute('user_id'),
            'team_id' => $this->getAttribute('team_id'),
            'title' => $this->getAttribute('title'),
            'content' => $this->getAttribute('content'),
            'data' => $this->getAttribute('data'),
            'status' => $this->getAttribute('status'),
        ];
    }
}

class Core05SubstitutionResource extends Core05MutationResource
{
    public static ?string $slug = 'core05-substitution';

    public static string $type = 'Core05Substitution';
}

class Core05MorphMutationResource extends Core05MutationResource
{
    public static ?string $slug = 'core05-morph-mutation';

    public static string $type = 'Core05MorphMutation';

    private string $mutationMorphClass = 'core05-trusted-morph';

    public function getMorphClass(): string
    {
        return $this->mutationMorphClass;
    }

    public function useMutationMorphClass(string $morphClass): static
    {
        $this->mutationMorphClass = $morphClass;

        return $this;
    }
}

class Core05UuidMutationResource extends BaseResource
{
    public array $actions = [
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public array $bulkActions = [
        'captureCollectionAttributes' => [
            'label' => 'Capture collection attributes',
            'ability' => 'update',
            'method' => 'collection',
        ],
        'markBulkReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public static $customTable = true;

    public $incrementing = false;

    public static ?string $slug = 'core05-uuid-mutation';

    public static string $type = 'Core05UuidMutation';

    public static bool $usesMeta = false;

    protected $baseFillable = ['id', 'title', 'content', 'status'];

    protected $fillable = ['id', 'title', 'content', 'status'];

    protected $keyType = 'string';

    protected $table = 'core05_uuid_mutation_resources';

    public function captureCollectionAttributes(array $ids): void
    {
        $this->content = json_encode($ids, JSON_THROW_ON_ERROR);
        $this->save();
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Status',
                'options' => [
                    [
                        'key' => 'draft',
                        'value' => 'Draft',
                        'color' => 'gray',
                    ],
                    [
                        'key' => 'reviewed',
                        'value' => 'Reviewed',
                        'color' => 'green',
                    ],
                ],
            ],
        ];
    }

    public function markBulkReviewed(): void
    {
        $this->content = 'reviewed-by-bulk-action';
        $this->save();
    }

    public function markReviewed(): void
    {
        $this->content = 'reviewed-by-action';
        $this->save();
    }

    public function resolveFieldValue(string $slug, mixed $meta = null): mixed
    {
        return $this->getAttribute($slug);
    }
}

class Core05MutationBoundaryPolicy
{
    public static int $attempts = 0;

    public function update(User $user, Core05MutationResource $resource): bool
    {
        if ($resource->exists) {
            static::$attempts++;
        }

        return $user->exists && $resource->exists;
    }
}

class Core05UuidMutationPolicy
{
    public function update(User $user, Core05UuidMutationResource $resource): bool
    {
        return $user->exists && $resource->exists;
    }
}

class Core05AuthoritativeCollisionPolicy
{
    public function update(User $user, Core05MutationResource $resource): bool
    {
        return $user->exists
            && (string) $resource->getAttribute('user_id') !== (string) $user->getKey()
            && (int) $resource->getAttribute('team_id') !== -900001
            && $resource->getAttribute('title') === 'Authoritative title'
            && $resource->getAttribute('content') === 'authoritative-content'
            && $resource->getAttribute('data') === 'authoritative-data'
            && $resource->getAttribute('status') === 'draft';
    }
}

class Core05PoisonCollisionPolicy
{
    public function update(User $user, Core05MutationResource $resource): bool
    {
        return (string) $resource->getAttribute('user_id') === (string) $user->getKey()
            && (int) $resource->getAttribute('team_id') === -900001
            && $resource->getAttribute('title') === 'Poisoned title'
            && $resource->getAttribute('content') === 'poisoned-content'
            && $resource->getAttribute('data') === 'poisoned-data'
            && $resource->getAttribute('status') === 'poisoned-status';
    }
}

class Core05MutationParentResource extends Resource
{
    public static ?string $slug = 'core05-mutation-parent';

    public static string $type = 'Core05MutationParent';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Children',
                'slug' => 'children',
                'type' => HasMany::class,
                'resource' => Core05MutationResource::class,
                'column' => 'parent_id',
            ],
        ];
    }
}

class Core05NoKanbanFieldResource extends Resource
{
    public static ?string $slug = 'core05-no-kanban-field';

    public static string $type = 'Core05NoKanbanField';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }
}

beforeEach(function () {
    Core05MutationBoundaryPolicy::$attempts = 0;
    Core05MutationResource::$useCollidingIndexQuery = false;
    Core05MutationResource::$updateInvocations = 0;
    config()->set('database.connections.core05_mutation_secondary', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    if (! Schema::hasColumn('posts', 'data')) {
        Schema::table('posts', function (Blueprint $table): void {
            $table->text('data')->nullable();
        });
    }
    if (! Schema::hasColumn('posts', 'alternate_id')) {
        Schema::table('posts', function (Blueprint $table): void {
            $table->unsignedBigInteger('alternate_id')->nullable()->unique();
        });
    }
    Schema::dropIfExists('core05_mutation_collisions');
    Schema::create('core05_mutation_collisions', function (Blueprint $table): void {
        $table->unsignedBigInteger('base_id');
        $table->unsignedBigInteger('id');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->bigInteger('team_id')->nullable();
        $table->string('title');
        $table->text('content')->nullable();
        $table->text('data')->nullable();
        $table->string('status')->nullable();
    });
    Schema::dropIfExists('core05_mutation_substitutions');
    Schema::create('core05_mutation_substitutions', function (Blueprint $table): void {
        $table->id();
        $table->string('type')->nullable();
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('status')->nullable();
        $table->string('slug')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->integer('order')->nullable();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::dropIfExists('core05_uuid_mutation_resources');
    Schema::create('core05_uuid_mutation_resources', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });
    Schema::connection('core05_mutation_secondary')->dropIfExists('posts');
    Schema::connection('core05_mutation_secondary')->create('posts', function (Blueprint $table): void {
        $table->id();
        $table->string('type')->nullable();
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('status')->nullable();
        $table->string('slug')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->integer('order')->nullable();
        $table->unsignedBigInteger('team_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::connection('core05_mutation_secondary')->dropIfExists('meta');
    Schema::connection('core05_mutation_secondary')->create('meta', function (Blueprint $table): void {
        $table->id();
        $table->string('metable_type');
        $table->unsignedBigInteger('metable_id');
        $table->string('key');
        $table->longText('value')->nullable();
        $table->timestamps();
    });

    Aura::fake();
    Aura::registerResources([
        Core05MorphMutationResource::class,
        Core05MutationResource::class,
        Core05MutationParentResource::class,
        Core05NoKanbanFieldResource::class,
        Core05SubstitutionResource::class,
        Core05UuidMutationResource::class,
    ]);
    Aura::setModel(new Core05MutationResource);
    Gate::policy(Core05UuidMutationResource::class, Core05UuidMutationPolicy::class);
});

/**
 * @return array{id: mixed, user_id: mixed, team_id: mixed, title: mixed, content: mixed, data: mixed, status: mixed}
 */
function core05AuthoritativeMutationSnapshot(Core05MutationResource $resource): array
{
    return [
        'id' => $resource->getKey(),
        'user_id' => $resource->getAttribute('user_id'),
        'team_id' => $resource->getAttribute('team_id'),
        'title' => $resource->getAttribute('title'),
        'content' => $resource->getAttribute('content'),
        'data' => $resource->getAttribute('data'),
        'status' => $resource->getAttribute('status'),
    ];
}

function core05CreateMutationCollision(
    Core05MutationResource $resource,
    User $user,
    bool $matchingId = false,
    int $duplicates = 1,
): void {
    $row = [
        'base_id' => $resource->getKey(),
        'id' => $matchingId ? $resource->getKey() : ((int) $resource->getKey()) + 100000,
        'user_id' => $user->getKey(),
        'team_id' => -900001,
        'title' => 'Poisoned title',
        'content' => 'poisoned-content',
        'data' => 'poisoned-data',
        'status' => 'poisoned-status',
    ];

    DB::table('core05_mutation_collisions')->insert(array_fill(0, $duplicates, $row));
    Core05MutationResource::$useCollidingIndexQuery = true;
}

function core05CreateAuthoritativeMutationResource(User $actor): Core05MutationResource
{
    $resource = Core05MutationResource::create([
        'title' => 'Authoritative title',
        'content' => 'authoritative-content',
        'status' => 'draft',
    ]);

    $attributes = [
        'user_id' => User::factory()->create()->getKey(),
        'data' => 'authoritative-data',
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $actor->getAttribute('current_team_id');
    }

    $resource->forceFill($attributes)->saveQuietly();

    return $resource->refresh();
}

function core05FailingMutationQuery(string $failure): string
{
    if ($failure === 'thrown callback') {
        return DynamicFunctions::add(static function (): Builder {
            throw new RuntimeException('declared mutation query failed');
        });
    }

    return 'core05-unregistered-mutation-query';
}

/**
 * @return array{mounted: Core05MutationResource, target: Core05MutationResource, id: int|string, query: string}
 */
function core05IdentitySubstitution(string $substitution, User $actor): array
{
    $attributes = [
        'title' => 'Identity substitution target',
        'content' => 'unchanged',
        'status' => 'draft',
    ];

    if (config('aura.teams')) {
        $attributes['team_id'] = $actor->getAttribute('current_team_id');
    }

    $mounted = new Core05MutationResource;

    [$dynamicModel, $target, $id] = match ($substitution) {
        'wrong class' => (function () use ($attributes): array {
            $target = Core05SubstitutionResource::create($attributes);

            return [new Core05SubstitutionResource, $target, $target->getKey()];
        })(),
        'same class different table' => (function () use ($attributes): array {
            $model = (new Core05MutationResource)->setTable('core05_mutation_substitutions');
            $target = $model->newQuery()->create($attributes);

            return [$model, $target, $target->getKey()];
        })(),
        'connection switch' => (function () use ($attributes): array {
            $model = (new Core05MutationResource)->setConnection('core05_mutation_secondary');
            $target = $model->newQuery()->create($attributes);

            return [$model, $target, $target->getKey()];
        })(),
        'key name switch' => (function () use ($attributes): array {
            $target = Core05MutationResource::create($attributes);
            $alternateId = ((int) $target->getKey()) + 100000;
            $target->forceFill(['alternate_id' => $alternateId])->saveQuietly();
            $model = (new Core05MutationResource)->setKeyName('alternate_id');

            return [$model, $target->refresh(), $alternateId];
        })(),
        'key type switch' => (function () use ($attributes): array {
            $target = Core05MutationResource::create($attributes);
            $model = (new Core05MutationResource)->setKeyType('string');

            return [$model, $target, $target->getKey()];
        })(),
        'morph switch' => (function () use ($attributes, &$mounted): array {
            $mounted = new Core05MorphMutationResource;
            $target = Core05MorphMutationResource::create($attributes);
            $model = (new Core05MorphMutationResource)->useMutationMorphClass('core05-substituted-morph');

            return [$model, $target, $target->getKey()];
        })(),
    };

    $queryHash = DynamicFunctions::add(static function () use ($dynamicModel): Builder {
        $query = $dynamicModel->newQuery();
        $query->getQuery()->beforeQuery(static function ($query): void {
            foreach ((array) $query->columns as $column) {
                if (is_string($column) && str_contains($column, '__aura_mutation_key')) {
                    throw new RuntimeException('An invalid mutation scope reached the database.');
                }
            }

            $query->from = 'posts';
            $query->orders = null;
            $query->wheres = [];
            $query->bindings['where'] = [];
            $query->whereRaw('0 = 1');
        });

        return $query;
    });

    return [
        'mounted' => $mounted,
        'target' => $target,
        'id' => $id,
        'query' => $queryHash,
    ];
}

function core05DeferredMutationSubstitution(string $substitution, int|string $id): string
{
    return DynamicFunctions::add(static function () use ($id, $substitution): Builder {
        $query = Core05MutationResource::query();
        $baseQuery = $query->getQuery();

        if ($substitution === 'before-query table switch') {
            $baseQuery->beforeQuery(static function ($query): void {
                $isMutationKeyQuery = collect((array) $query->columns)->contains(
                    fn (mixed $column): bool => is_string($column)
                        && str_contains($column, '__aura_mutation_key'),
                );

                if ($isMutationKeyQuery) {
                    $query->from = 'core05_mutation_substitutions as posts';

                    return;
                }

                $query->from = 'posts';
                $query->orders = null;
                $query->wheres = [];
                $query->bindings['where'] = [];
                $query->whereRaw('0 = 1');
            });
        }

        if ($substitution === 'after-query key injection') {
            $queryState = (object) ['isMutationKeyQuery' => false];
            $query->whereRaw('0 = 1');
            $baseQuery->beforeQuery(static function ($query) use ($queryState): void {
                $queryState->isMutationKeyQuery = collect((array) $query->columns)->contains(
                    fn (mixed $column): bool => is_string($column)
                        && str_contains($column, '__aura_mutation_key'),
                );
            });

            $baseQuery->afterQuery(static function ($rows) use ($id, $queryState) {
                return $queryState->isMutationKeyQuery
                    ? collect([(object) ['__aura_mutation_key' => $id]])
                    : $rows;
            });
        }

        return $query;
    });
}

function core05CallMutationSurface(
    string $surface,
    ?string $query,
    Core05MutationResource|Core05UuidMutationResource $mounted,
    int|string $id,
): mixed {
    return match ($surface) {
        'single action' => livewire(Table::class, ['query' => $query, 'model' => $mounted])
            ->call('action', ['action' => 'markReviewed', 'id' => $id]),
        'bulk record' => livewire(Table::class, ['query' => $query, 'model' => $mounted])
            ->set('selected', [$id])
            ->call('bulkAction', 'markBulkReviewed'),
        'bulk collection' => livewire(Table::class, ['query' => $query, 'model' => $mounted])
            ->set('selected', [$id])
            ->call('bulkCollectionAction', 'captureCollectionAttributes'),
        'Kanban update' => livewire(Table::class, ['query' => $query, 'model' => $mounted])
            ->call('updateCardStatus', $id, 'reviewed'),
    };
}

test('table action rejects an undeclared model method', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Keep me',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'delete', 'id' => $resource->id])
        ->assertStatus(403);

    expect(Core05MutationResource::find($resource->id))->not->toBeNull();
});

test('table action denies a declared mutation when its policy denies the record', function () {
    $user = createAdmin();
    $user->roles()->first()->update([
        'permissions' => [
            'viewAny-core05-mutation' => true,
            'view-core05-mutation' => true,
            'update-core05-mutation' => false,
        ],
    ]);
    $this->actingAs($user->refresh());

    $resource = Core05MutationResource::create([
        'title' => 'Protected',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->id])
        ->assertStatus(403);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action uses the destructive policy ability for a declared delete action', function () {
    $user = createAdmin();
    $user->roles()->first()->update([
        'permissions' => [
            'viewAny-core05-mutation' => true,
            'view-core05-mutation' => true,
            'update-core05-mutation' => true,
            'delete-core05-mutation' => false,
        ],
    ]);
    $this->actingAs($user->refresh());

    $resource = Core05MutationResource::create([
        'title' => 'Not deletable',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'deleteRecord', 'id' => $resource->id])
        ->assertStatus(403);

    expect(Core05MutationResource::find($resource->id))->not->toBeNull();
});

test('table action validates the client-provided record identifier', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Unchanged',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'markReviewed', 'id' => [$resource->id]])
        ->assertHasErrors(['id']);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action rejects a declared action whose condition is false', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Hidden action',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'hiddenAction', 'id' => $resource->id])
        ->assertStatus(403);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action rejects a declared action without a real model method', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Missing action',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'missingAction', 'id' => $resource->id])
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action rejects client parameters for a method that requires arguments', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Parameterized action',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', [
            'action' => 'parameterizedAction',
            'id' => $resource->id,
            'parameters' => ['forged-content'],
        ])
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action runs an authorized declared mutation', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Action target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('reviewed-by-action');
});

test('kanban status change denies a record the policy does not allow updating', function () {
    $user = createAdmin();
    $user->roles()->first()->update([
        'permissions' => [
            'viewAny-core05-mutation' => true,
            'view-core05-mutation' => true,
            'update-core05-mutation' => false,
        ],
    ]);
    $this->actingAs($user->refresh());

    $resource = Core05MutationResource::create([
        'title' => 'Protected card',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'reviewed')
        ->assertStatus(403);

    expect($resource->fresh()->status)->toBe('draft');
});

test('kanban status change rejects a value outside the declared field options', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Validated card',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'forged-status')
        ->assertHasErrors(['kanbanStatus']);

    expect($resource->fresh()->status)->toBe('draft');
});

test('kanban status change validates client-provided identifiers and values', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Malformed move',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', [$resource->id], ['reviewed'])
        ->assertHasErrors(['cardId', 'kanbanStatus']);

    expect($resource->fresh()->status)->toBe('draft');
});

test('kanban status change persists an authorized declared option', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Movable card',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'reviewed')
        ->assertHasNoErrors();

    expect($resource->fresh()->status)->toBe('reviewed');
});

test('kanban status change rejects a resource without the configured group field', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05NoKanbanFieldResource::create([
        'title' => 'No Kanban field',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'reviewed')
        ->assertHasErrors(['kanbanField']);

    expect($resource->fresh()->status)->toBe('draft');
});

test('table action cannot resolve a record from another team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation only applies when teams are enabled.');
    }

    $this->actingAs(createSuperAdmin());

    $otherTeam = foreignTeam();
    $foreignResource = Core05MutationResource::withoutGlobalScopes()->create([
        'title' => 'Other team',
        'content' => 'unchanged',
        'status' => 'draft',
        'team_id' => $otherTeam->id,
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $foreignResource->id])
        ->assertNotFound();

    expect(Core05MutationResource::withoutGlobalScopes()->findOrFail($foreignResource->id)->content)
        ->toBe('unchanged');
});

test('kanban status change cannot resolve a record from another team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation only applies when teams are enabled.');
    }

    $this->actingAs(createSuperAdmin());

    $otherTeam = foreignTeam();
    $foreignResource = Core05MutationResource::withoutGlobalScopes()->create([
        'title' => 'Other team card',
        'status' => 'draft',
        'team_id' => $otherTeam->id,
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $foreignResource->id, 'reviewed')
        ->assertNotFound();

    expect(Core05MutationResource::withoutGlobalScopes()->findOrFail($foreignResource->id)->status)
        ->toBe('draft');
});

test('table and kanban mutations reject a forged record id', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Existing resource',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $forgedId = $resource->id + 100000;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $forgedId])
        ->assertNotFound();

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $forgedId, 'reviewed')
        ->assertNotFound();

    $freshResource = $resource->fresh();

    expect($freshResource->content)->toBe('unchanged')
        ->and($freshResource->status)->toBe('draft');
});

test('table action cannot mutate a record excluded by the resource index query', function () {
    $this->actingAs(createSuperAdmin());

    $excluded = Core05MutationResource::create([
        'title' => 'Excluded by indexQuery',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $excluded->id])
        ->assertNotFound();

    expect($excluded->fresh()->content)->toBe('unchanged');
});

test('kanban cannot mutate a record excluded by the resource index query', function () {
    $this->actingAs(createSuperAdmin());

    $excluded = Core05MutationResource::create([
        'title' => 'Excluded by indexQuery',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $excluded->id, 'reviewed')
        ->assertNotFound();

    expect($excluded->fresh()->status)->toBe('draft');
});

test('table action cannot mutate a same-type record outside the parent relationship', function () {
    $this->actingAs(createSuperAdmin());

    $parent = Core05MutationParentResource::create(['title' => 'Parent']);
    $otherParent = Core05MutationParentResource::create(['title' => 'Other parent']);
    $related = Core05MutationResource::create([
        'title' => 'Related',
        'content' => 'unchanged',
        'status' => 'draft',
        'parent_id' => $parent->id,
    ]);
    $unrelated = Core05MutationResource::create([
        'title' => 'Unrelated',
        'content' => 'unchanged',
        'status' => 'draft',
        'parent_id' => $otherParent->id,
    ]);

    livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
        'parent' => $parent,
        'field' => $parent->fieldBySlug('children'),
    ])->call('action', ['action' => 'markReviewed', 'id' => $unrelated->id])
        ->assertNotFound();

    livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
        'parent' => $parent,
        'field' => $parent->fieldBySlug('children'),
    ])->call('updateCardStatus', $unrelated->id, 'reviewed')
        ->assertNotFound();

    livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
        'parent' => $parent,
        'field' => $parent->fieldBySlug('children'),
    ])->set('selected', [$related->id, $unrelated->id])
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasErrors(['selected']);

    expect($related->fresh()->content)->toBe('unchanged')
        ->and($related->fresh()->status)->toBe('draft')
        ->and($unrelated->fresh()->content)->toBe('unchanged')
        ->and($unrelated->fresh()->status)->toBe('draft');
});

test('table action cannot mutate a record excluded by a declared dynamic query', function () {
    $this->actingAs(createSuperAdmin());

    $visible = Core05MutationResource::create([
        'title' => 'Visible dynamic row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $excluded = Core05MutationResource::create([
        'title' => 'Excluded dynamic row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()->whereKey($visible->id)
    );

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $excluded->id])
        ->assertNotFound();

    expect($excluded->fresh()->content)->toBe('unchanged');
});

test('cosmetic table search does not narrow the mutation authorization scope', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Action target outside search',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('search', 'No matching row')
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('reviewed-by-action');
});

test('custom table action without an explicit ability fails closed', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Custom action target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'customWithoutAbility', 'id' => $resource->id])
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('kanban mutation always applies the declared Kanban query scope', function () {
    $this->actingAs(createSuperAdmin());

    $excluded = Core05MutationResource::create([
        'title' => 'Excluded by kanbanQuery',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $excluded->id, 'reviewed')
        ->assertNotFound();

    expect($excluded->fresh()->status)->toBe('draft');
});

test('declared dynamic mutation scope cannot be widened through Livewire state tampering', function () {
    $this->actingAs(createSuperAdmin());

    $visible = Core05MutationResource::create([
        'title' => 'Locked visible row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $excluded = Core05MutationResource::create([
        'title' => 'Locked excluded row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $restrictedQuery = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()->whereKey($visible->id)
    );
    $widenedQuery = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()
    );

    expect(fn () => livewire(Table::class, [
        'query' => $restrictedQuery,
        'model' => new Core05MutationResource,
    ])->set('query', $widenedQuery)
        ->call('action', ['action' => 'markReviewed', 'id' => $excluded->id]))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    expect($excluded->fresh()->content)->toBe('unchanged');
});

test('mutation scope identity substitutions fail before querying authorization or handlers', function (
    string $surface,
    string $substitution,
) {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);
    Gate::policy(Core05MorphMutationResource::class, Core05MutationBoundaryPolicy::class);
    Gate::policy(Core05SubstitutionResource::class, Core05MutationBoundaryPolicy::class);

    $case = core05IdentitySubstitution($substitution, $actor);
    Core05MutationResource::$updateInvocations = 0;

    core05CallMutationSurface($surface, $case['query'], $case['mounted'], $case['id'])
        ->assertStatus(422);

    $target = $case['target']->fresh();

    expect(Core05MutationBoundaryPolicy::$attempts)->toBe(0)
        ->and(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($target->content)->toBe('unchanged')
        ->and($target->status)->toBe('draft');
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
])->with([
    'wrong class' => 'wrong class',
    'same class different table' => 'same class different table',
    'connection switch' => 'connection switch',
    'key name switch' => 'key name switch',
    'key type switch' => 'key type switch',
    'morph switch' => 'morph switch',
]);

test('deferred mutation scope identity substitutions fail before authorization or handlers', function (
    string $surface,
    string $substitution,
) {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Deferred identity substitution target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    DB::table('core05_mutation_substitutions')->insert([
        'id' => $resource->getKey(),
        'type' => Core05MutationResource::$type,
        'title' => $resource->title,
        'content' => 'substituted',
        'status' => $resource->status,
        'user_id' => $resource->user_id,
        'team_id' => $resource->team_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $queryHash = core05DeferredMutationSubstitution($substitution, $resource->getKey());

    $result = core05CallMutationSurface(
        $surface,
        $queryHash,
        new Core05MutationResource,
        $resource->getKey(),
    );

    match (true) {
        $substitution === 'before-query table switch' => $result->assertStatus(422),
        in_array($surface, ['bulk record', 'bulk collection'], true) => $result->assertHasErrors(['selected']),
        default => $result->assertNotFound(),
    };

    $freshResource = $resource->fresh();

    expect(Core05MutationBoundaryPolicy::$attempts)->toBe(0)
        ->and(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($freshResource->content)->toBe('unchanged')
        ->and($freshResource->status)->toBe('draft');
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
])->with([
    'before-query table switch' => 'before-query table switch',
    'after-query key injection' => 'after-query key injection',
]);

test('matching UUID mutation identities remain exact on every mutation surface', function (string $surface) {
    $this->actingAs(createSuperAdmin());

    $id = (string) Str::uuid();
    $resource = Core05UuidMutationResource::create([
        'id' => $id,
        'title' => 'UUID mutation target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05UuidMutationResource::query()->whereKey($id)
    );

    core05CallMutationSurface($surface, $queryHash, new Core05UuidMutationResource, $id)
        ->assertHasNoErrors();

    $freshResource = $resource->fresh();

    expect($freshResource->getKey())->toBe($id);

    match ($surface) {
        'single action' => expect($freshResource->content)->toBe('reviewed-by-action'),
        'bulk record' => expect($freshResource->content)->toBe('reviewed-by-bulk-action'),
        'bulk collection' => expect(
            json_decode($freshResource->content, true, flags: JSON_THROW_ON_ERROR)
        )->toBe([$id]),
        'Kanban update' => expect($freshResource->status)->toBe('reviewed'),
    };
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
]);

test('joined index columns cannot poison a single action policy or handler model', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05AuthoritativeCollisionPolicy::class);

    $resource = core05CreateAuthoritativeMutationResource($actor);
    $expectedSnapshot = core05AuthoritativeMutationSnapshot($resource);
    core05CreateMutationCollision($resource, $actor);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', [
            'action' => 'captureAuthoritativeAttributes',
            'id' => $resource->getKey(),
        ])
        ->assertHasNoErrors();

    expect(json_decode($resource->fresh()->content, true, flags: JSON_THROW_ON_ERROR))
        ->toBe($expectedSnapshot);
});

test('joined index columns cannot poison a bulk record policy or handler model', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05AuthoritativeCollisionPolicy::class);

    $resource = core05CreateAuthoritativeMutationResource($actor);
    $expectedSnapshot = core05AuthoritativeMutationSnapshot($resource);
    core05CreateMutationCollision($resource, $actor);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'captureAuthoritativeAttributes')
        ->assertHasNoErrors();

    expect(json_decode($resource->fresh()->content, true, flags: JSON_THROW_ON_ERROR))
        ->toBe($expectedSnapshot);
});

test('joined index columns cannot poison a bulk collection receiver or canonical ids', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05AuthoritativeCollisionPolicy::class);

    $resource = core05CreateAuthoritativeMutationResource($actor);
    $expectedSnapshot = core05AuthoritativeMutationSnapshot($resource);
    $expectedSnapshot['ids'] = [$resource->getKey()];
    core05CreateMutationCollision($resource, $actor);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkCollectionAction', 'captureCollectionAttributes')
        ->assertHasNoErrors();

    expect(json_decode($resource->fresh()->content, true, flags: JSON_THROW_ON_ERROR))
        ->toBe($expectedSnapshot);
});

test('joined index columns cannot poison Kanban authorization or its target model', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05AuthoritativeCollisionPolicy::class);

    $resource = core05CreateAuthoritativeMutationResource($actor);
    core05CreateMutationCollision($resource, $actor);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $resource->getKey(), 'reviewed')
        ->assertHasNoErrors();

    expect($resource->fresh()->status)->toBe('reviewed');
});

test('matching joined ids cannot smuggle poisoned attributes through policy authorization', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05PoisonCollisionPolicy::class);

    $resource = core05CreateAuthoritativeMutationResource($actor);
    core05CreateMutationCollision($resource, $actor, matchingId: true);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->getKey()])
        ->assertStatus(403);

    expect($resource->fresh()->content)->toBe('authoritative-content');
});

test('poisoned duplicate joins still invoke one authoritative record once', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $resource = Core05MutationResource::create([
        'title' => 'Authoritative title',
        'content' => '0',
        'status' => 'draft',
    ]);
    core05CreateMutationCollision($resource, $actor, duplicates: 2);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'incrementInvocation', 'id' => $resource->getKey()])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('1');
});

test('authoritative rehydration reapplies the resource type scope to dynamic mutation queries', function () {
    $this->actingAs(createSuperAdmin());

    $foreignType = Core05NoKanbanFieldResource::create([
        'title' => 'Different resource type',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::withoutGlobalScopes()->whereKey($foreignType->getKey())
    );

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $foreignType->getKey()])
        ->assertNotFound();

    expect(Core05NoKanbanFieldResource::findOrFail($foreignType->getKey())->content)->toBe('unchanged');
});

test('authoritative rehydration reapplies the team scope to dynamic mutation queries', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation only applies when teams are enabled.');
    }

    $this->actingAs(createSuperAdmin());

    $foreignTeam = foreignTeam();
    $foreignResource = Core05MutationResource::withoutGlobalScopes()->create([
        'title' => 'Foreign unscoped dynamic row',
        'content' => 'unchanged',
        'status' => 'draft',
        'team_id' => $foreignTeam->getKey(),
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::withoutGlobalScopes()->whereKey($foreignResource->getKey())
    );

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $foreignResource->getKey()])
        ->assertNotFound();

    expect(Core05MutationResource::withoutGlobalScopes()->findOrFail($foreignResource->getKey())->content)
        ->toBe('unchanged');
});

test('declared dynamic query failures abort every mutation surface', function (string $surface, string $failure) {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Fail closed target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = core05FailingMutationQuery($failure);

    $expectedException = match ($failure) {
        'missing facade root' => BindingResolutionException::class,
        'missing callback' => Exception::class,
        'thrown callback' => RuntimeException::class,
    };

    if ($failure === 'missing facade root') {
        app()->forgetInstance('dynamicFunctions');
        app()->offsetUnset('dynamicFunctions');
    }

    $mutate = match ($surface) {
        'single action' => fn () => livewire(Table::class, [
            'query' => $queryHash,
            'model' => new Core05MutationResource,
        ])->call('action', ['action' => 'markReviewed', 'id' => $resource->getKey()]),
        'bulk action' => fn () => livewire(Table::class, [
            'query' => $queryHash,
            'model' => new Core05MutationResource,
        ])->set('selected', [$resource->getKey()])
            ->call('bulkAction', 'markBulkReviewed'),
        'bulk collection' => fn () => livewire(Table::class, [
            'query' => $queryHash,
            'model' => new Core05MutationResource,
        ])->set('selected', [$resource->getKey()])
            ->call('bulkCollectionAction', 'captureCollectionAttributes'),
        'Kanban update' => fn () => livewire(Table::class, [
            'query' => $queryHash,
            'model' => new Core05MutationResource,
        ])->call('updateCardStatus', $resource->getKey(), 'reviewed'),
    };

    expect($mutate)->toThrow($expectedException);

    $freshResource = $resource->fresh();

    expect($freshResource->content)->toBe('unchanged')
        ->and($freshResource->status)->toBe('draft');
})->with([
    'single action' => 'single action',
    'bulk action' => 'bulk action',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
])->with([
    'missing facade root' => 'missing facade root',
    'missing callback' => 'missing callback',
    'thrown callback' => 'thrown callback',
]);

test('table action invokes one canonical record when an effective query returns duplicate rows', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Joined row target',
        'content' => '0',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(function (): Builder {
        $duplicates = DB::query()
            ->selectRaw('1 as duplicate_marker')
            ->unionAll(DB::query()->selectRaw('2 as duplicate_marker'));

        return Core05MutationResource::query()->crossJoinSub($duplicates, 'core05_row_duplicates');
    });

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'incrementInvocation', 'id' => $resource->getKey()])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('1');
});

test('kanban updates one canonical record when an effective query returns duplicate rows', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Joined card target',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(function (): Builder {
        $duplicates = DB::query()
            ->selectRaw('1 as duplicate_marker')
            ->unionAll(DB::query()->selectRaw('2 as duplicate_marker'));

        return Core05MutationResource::query()->crossJoinSub($duplicates, 'core05_kanban_duplicates');
    });
    Core05MutationResource::$updateInvocations = 0;

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $resource->getKey(), 'reviewed')
        ->assertHasNoErrors();

    expect($resource->fresh()->status)->toBe('reviewed')
        ->and(Core05MutationResource::$updateInvocations)->toBe(1);
});

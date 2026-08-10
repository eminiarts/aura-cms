<?php

use Aura\Base\BaseResource;
use Aura\Base\Contracts\ContextualFieldProvider;
use Aura\Base\Facades\Aura;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\FieldProviderContext;
use Aura\Base\Fields\File as FileField;
use Aura\Base\Fields\HasMany;
use Aura\Base\Fields\Image;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Livewire\Modals;
use Aura\Base\Livewire\Profile;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Settings;
use Aura\Base\Livewire\SignedModalRequest;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Livewire\Table\TableMutationDispatcher;
use Aura\Base\Livewire\Table\TableMutationModelDescriptor;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use function Pest\Livewire\livewire;

class Core05LockObservingSQLiteGrammar extends SQLiteGrammar
{
    protected function compileLock(QueryBuilder $query, $value)
    {
        return $value ? '/* core05-lock-for-update */' : '';
    }
}

class Core05ContextualSortFieldState
{
    public static bool $available = true;
}

class Core05ContextualSortFieldProvider implements ContextualFieldProvider
{
    public function cacheContext(string $resourceClass): array
    {
        return ['available' => Core05ContextualSortFieldState::$available];
    }

    public function cacheVersion(FieldProviderContext $context): string|int
    {
        return 1;
    }

    public function fields(FieldProviderContext $context): array
    {
        if (! $context->value('available')) {
            return [];
        }

        return [[
            'name' => 'Contextual amount',
            'slug' => 'content',
            'type' => 'Aura\\Base\\Fields\\Number',
            'number_type' => 'decimal',
            'precision' => 20,
            'scale' => 4,
        ]];
    }

    public function managedFieldSlugs(string $resourceClass): array
    {
        return ['content'];
    }
}

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
        'deadlockAfterExternalEffect' => [
            'label' => 'Deadlock after external effect',
            'ability' => 'update',
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

    public static ?string $authoritativeQueryCallback = null;

    public static ?Closure $authoritativeReadCallback = null;

    /** @var array<int, bool|string|null> */
    public static array $authoritativeReadLocks = [];

    /** @var array<int, array<int, array<string, mixed>>|null> */
    public static array $authoritativeReadOrders = [];

    /** @var array<int, int> */
    public static array $authoritativeReadTransactionLevels = [];

    public static ?Closure $beforeQueryCallback = null;

    public static int $beforeQueryInvocations = 0;

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
        'captureCollectionOrder' => [
            'label' => 'Capture collection order',
            'ability' => 'update',
            'method' => 'collection',
        ],
        'markBulkReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
        'openReviewModal' => [
            'label' => 'Review',
            'ability' => 'update',
            'modal' => 'core05-authorized-bulk-modal',
        ],
    ];

    /** @var array<int, array<int, int|string>> */
    public static array $capturedCollectionIdChunks = [];

    public static bool $countIndexBeforeQueryInvocations = false;

    public static int $dynamicBeforeQueryInvocations = 0;

    public static int $externalEffects = 0;

    public static int $indexBeforeQueryInvocations = 0;

    public static ?string $slug = 'core05-mutation';

    public static string $type = 'Core05Mutation';

    public static int $updateInvocations = 0;

    /** @var array<int, int> */
    public static array $updateTransactionLevels = [];

    public static bool $useCollidingIndexQuery = false;

    public function captureAuthoritativeAttributes(): void
    {
        $this->content = json_encode($this->mutationAttributeSnapshot(), JSON_THROW_ON_ERROR);
        $this->save();
    }

    public function captureCollectionAttributes(array $ids): void
    {
        static::$capturedCollectionIdChunks[] = $ids;
        $snapshot = $this->mutationAttributeSnapshot();
        $snapshot['ids'] = $ids;

        $this->content = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $this->save();
    }

    public function captureCollectionOrder(array $ids): void
    {
        static::$capturedCollectionIdChunks[] = $ids;
    }

    public function customWithoutAbility(): void
    {
        $this->content = 'custom-action-ran';
        $this->save();
    }

    public function deadlockAfterExternalEffect(): void
    {
        static::$externalEffects++;

        $this->content = 'changed-before-deadlock';
        $this->save();

        if (static::$externalEffects === 1) {
            throw new PDOException('database is locked');
        }
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
                'searchable' => true,
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

        if (static::$countIndexBeforeQueryInvocations) {
            $resourceClass = static::class;

            $query->getQuery()->beforeQuery(static function (QueryBuilder $query) use ($resourceClass): void {
                $isMutationKeyQuery = collect((array) $query->columns)->contains(
                    fn (mixed $column): bool => is_string($column)
                        && str_contains($column, '__aura_mutation_key'),
                );

                if ($isMutationKeyQuery) {
                    $resourceClass::$indexBeforeQueryInvocations++;
                }
            });
        }

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

    public function kanbanSettings(): array
    {
        return [
            'enabled' => true,
            'group_field' => 'status',
            'columns' => ['draft', 'reviewed'],
            'card_title' => 'title',
            'card_subtitle' => null,
            'order_by' => null,
            'show_empty_columns' => true,
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

    public function parameterizedAction(string $content): void
    {
        $this->content = $content;
        $this->save();
    }

    protected static function booted(): void
    {
        parent::booted();

        $resourceClass = static::class;

        static::addGlobalScope('core05-authoritative-query-callback', static function (Builder $builder) use (
            $resourceClass,
        ): void {
            $callback = $resourceClass::$authoritativeQueryCallback;

            if (
                $callback === null
                && ! $resourceClass::$authoritativeReadCallback instanceof Closure
                && ! $resourceClass::$beforeQueryCallback instanceof Closure
            ) {
                return;
            }

            $baseQuery = $builder->getQuery();

            if ($resourceClass::$beforeQueryCallback instanceof Closure) {
                $baseQuery->beforeQuery(static function ($query) use ($resourceClass): void {
                    $isMutationQuery = $query->lock !== null
                        || collect((array) $query->columns)->contains(
                            fn (mixed $column): bool => is_string($column)
                                && str_contains($column, '__aura_mutation_key'),
                        );

                    if (! $isMutationQuery) {
                        return;
                    }

                    $resourceClass::$beforeQueryInvocations++;

                    if ($resourceClass::$beforeQueryCallback instanceof Closure) {
                        ($resourceClass::$beforeQueryCallback)($query);
                    }
                });
            }

            if ($callback === null && ! $resourceClass::$authoritativeReadCallback instanceof Closure) {
                return;
            }

            $queryState = (object) ['isAuthoritativeRead' => false];
            $qualifiedWildcard = $builder->getModel()->qualifyColumn('*');

            $baseQuery->beforeQuery(static function ($query) use (
                $callback,
                $qualifiedWildcard,
                $queryState,
                $resourceClass,
            ): void {
                $queryState->isAuthoritativeRead = in_array($qualifiedWildcard, (array) $query->columns, true)
                    || collect((array) $query->columns)->contains(
                        fn (mixed $column): bool => is_string($column)
                            && str_contains($column, '__aura_mutation_key'),
                    );

                if (! $queryState->isAuthoritativeRead) {
                    return;
                }

                $resourceClass::$authoritativeReadLocks[] = $query->lock;
                $resourceClass::$authoritativeReadOrders[] = $query->orders;
                $resourceClass::$authoritativeReadTransactionLevels[] = $query->getConnection()->transactionLevel();

                if ($resourceClass::$authoritativeReadCallback instanceof Closure) {
                    ($resourceClass::$authoritativeReadCallback)($query);
                }

                if ($callback === 'before-query table switch') {
                    $query->from = 'core05_mutation_substitutions as posts';
                }
            });

            if ($callback === 'after-query model injection') {
                $builder->afterQuery(static function ($records) use ($queryState) {
                    if (! $queryState->isAuthoritativeRead) {
                        return $records;
                    }

                    return $records->map(static function (Core05MutationResource $record) {
                        $poisonedRecord = clone $record;
                        $poisonedRecord->forceFill([
                            'title' => 'Poisoned callback title',
                            'content' => 'poisoned-callback-content',
                            'status' => 'draft',
                        ]);

                        return $poisonedRecord;
                    });
                });
            }
        });

        static::updating(function (Core05MutationResource $resource): void {
            static::$updateInvocations++;
            static::$updateTransactionLevels[] = $resource->getConnection()->transactionLevel();
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

class Core05MediaResource extends Resource
{
    public static ?string $slug = 'core05-media';

    public static string $type = 'Core05Media';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Hero image',
                'slug' => 'hero_image',
                'type' => Image::class,
            ],
            [
                'name' => 'Document',
                'slug' => 'document',
                'type' => FileField::class,
            ],
        ];
    }
}

class Core05DangerousContainerBinding
{
    public static int $constructions = 0;

    public function __construct()
    {
        static::$constructions++;
    }
}

class Core05DenyMediaPolicy
{
    public function viewAny(User $user, Core05MediaResource $resource): bool
    {
        return false;
    }
}

class Core05ToggleMediaPolicy
{
    public static bool $allowed = true;

    public function viewAny(User $user, Core05MediaResource $resource): bool
    {
        return static::$allowed;
    }
}

class Core05DenyAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }
}

class Core05DenyAttachmentCreatePolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}

class Core05AuthorizedBulkModal extends Component
{
    public static int $mounts = 0;

    public function mount(array $selected, string $model, string $action): void
    {
        static::$mounts++;
    }

    public function render(): string
    {
        return '<div>Authorized bulk modal</div>';
    }
}

class Core05ForgedModal extends Component
{
    public static int $mounts = 0;

    public function mount(): void
    {
        static::$mounts++;
    }

    public function render(): string
    {
        return '<div>Forged modal</div>';
    }
}

class Core05EagerMutationResource extends Core05MutationResource
{
    public static int $relationBeforeQueryInvocations = 0;

    public static ?int $relationExpectedTransactionLevel = null;

    /** @var array<int, string> */
    protected $with = ['callbackUser'];

    public function callbackUser(): BelongsTo
    {
        $resourceClass = static::class;
        $relation = $this->belongsTo(User::class, 'user_id');

        $relation->getQuery()->getQuery()->beforeQuery(static function (QueryBuilder $query) use (
            $resourceClass,
        ): void {
            if (
                $resourceClass::$relationExpectedTransactionLevel !== null
                && $query->getConnection()->transactionLevel() === $resourceClass::$relationExpectedTransactionLevel
            ) {
                $resourceClass::$relationBeforeQueryInvocations++;
            }
        });

        return $relation;
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

    public function kanbanSettings(): array
    {
        return [
            'enabled' => true,
            'group_field' => 'status',
            'columns' => ['draft', 'reviewed'],
            'card_title' => 'title',
            'card_subtitle' => null,
            'order_by' => null,
            'show_empty_columns' => true,
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

    /** @var array<int, int|string|null> */
    public static array $teamContexts = [];

    /** @var array<int, int> */
    public static array $transactionLevels = [];

    public function update(User $user, Core05MutationResource $resource): bool
    {
        if ($resource->exists) {
            static::$attempts++;
            static::$teamContexts[] = TeamScope::currentContextTeamId($resource->getConnection());

            if ($resource->getConnection()->transactionLevel() > 0) {
                static::$transactionLevels[] = $resource->getConnection()->transactionLevel();
            }
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

class Core05AuthoritativeCallbackPolicy
{
    public static int $attempts = 0;

    /** @var array<int, array{title: mixed, content: mixed, status: mixed}> */
    public static array $snapshots = [];

    public function update(User $user, Core05MutationResource $resource): bool
    {
        if (! $resource->exists) {
            return $user->exists;
        }

        static::$attempts++;
        static::$snapshots[] = [
            'title' => $resource->getAttribute('title'),
            'content' => $resource->getAttribute('content'),
            'status' => $resource->getAttribute('status'),
        ];

        return $user->exists
            && $resource->getAttribute('title') === 'Authoritative callback target'
            && $resource->getAttribute('content') === 'authoritative-callback-content'
            && $resource->getAttribute('status') === 'draft';
    }
}

class Core05MorphMutationPolicy
{
    public static int $attempts = 0;

    /** @var array<int, string> */
    public static array $morphClasses = [];

    public function update(User $user, Core05MorphMutationResource $resource): bool
    {
        if (! $resource->exists) {
            return $user->exists;
        }

        static::$attempts++;
        static::$morphClasses[] = $resource->getMorphClass();

        return $user->exists && $resource->getMorphClass() === 'core05-instance-morph';
    }
}

class Core05TransactionMutationPolicy
{
    /** @var array<int, int> */
    public static array $transactionLevels = [];

    public function update(User $user, Core05MutationResource $resource): bool
    {
        if (! $resource->exists) {
            return $user->exists;
        }

        static::$transactionLevels[] = $resource->getConnection()->transactionLevel();

        return $user->exists && $resource->exists;
    }
}

class Core05DenyingMutationPolicy
{
    public function update(User $user, Core05MutationResource $resource): bool
    {
        if (! $resource->exists) {
            return $user->exists;
        }

        $resource->getConnection()->table($resource->getTable())
            ->where($resource->getKeyName(), $resource->getKey())
            ->update(['content' => 'changed-during-authorization']);

        return false;
    }
}

class Core05DenyLastChunkMutationPolicy
{
    public static int|string|null $deniedKey = null;

    public function update(User $user, Core05MutationResource $resource): bool
    {
        if (! $resource->exists) {
            return $user->exists;
        }

        $resource->getConnection()->table($resource->getTable())
            ->where($resource->getKeyName(), $resource->getKey())
            ->update(['content' => 'changed-during-chunked-authorization']);

        return (string) $resource->getKey() !== (string) static::$deniedKey;
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
    Core05ContextualSortFieldState::$available = true;
    Core05AuthorizedBulkModal::$mounts = 0;
    Core05AuthoritativeCallbackPolicy::$attempts = 0;
    Core05AuthoritativeCallbackPolicy::$snapshots = [];
    Core05MorphMutationPolicy::$attempts = 0;
    Core05MorphMutationPolicy::$morphClasses = [];
    Core05MutationBoundaryPolicy::$attempts = 0;
    Core05MutationBoundaryPolicy::$teamContexts = [];
    Core05MutationBoundaryPolicy::$transactionLevels = [];
    Core05EagerMutationResource::$relationBeforeQueryInvocations = 0;
    Core05EagerMutationResource::$relationExpectedTransactionLevel = null;
    Core05MutationResource::$authoritativeQueryCallback = null;
    Core05MutationResource::$authoritativeReadCallback = null;
    Core05MutationResource::$beforeQueryCallback = null;
    Core05MutationResource::$beforeQueryInvocations = 0;
    Core05MutationResource::$capturedCollectionIdChunks = [];
    Core05MutationResource::$countIndexBeforeQueryInvocations = false;
    Core05MutationResource::$dynamicBeforeQueryInvocations = 0;
    Core05MutationResource::$indexBeforeQueryInvocations = 0;
    Core05MutationResource::$authoritativeReadLocks = [];
    Core05MutationResource::$authoritativeReadOrders = [];
    Core05MutationResource::$authoritativeReadTransactionLevels = [];
    Core05MutationResource::$useCollidingIndexQuery = false;
    Core05MutationResource::$externalEffects = 0;
    Core05MutationResource::$updateInvocations = 0;
    Core05MutationResource::$updateTransactionLevels = [];
    Core05ForgedModal::$mounts = 0;
    Core05DangerousContainerBinding::$constructions = 0;
    Core05DenyLastChunkMutationPolicy::$deniedKey = null;
    Core05ToggleMediaPolicy::$allowed = true;
    Core05TransactionMutationPolicy::$transactionLevels = [];
    Livewire::component('core05-authorized-bulk-modal', Core05AuthorizedBulkModal::class);
    Livewire::component('core05-forged-modal', Core05ForgedModal::class);
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
    Schema::connection('core05_mutation_secondary')->dropIfExists('users');
    Schema::connection('core05_mutation_secondary')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('current_team_id')->nullable();
    });
    Schema::connection('core05_mutation_secondary')->dropIfExists('user_role');
    Schema::connection('core05_mutation_secondary')->create('user_role', function (Blueprint $table): void {
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('role_id');
        $table->unsignedBigInteger('team_id')->nullable();
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
        Core05MediaResource::class,
        Core05MutationResource::class,
        Core05MutationParentResource::class,
        Core05NoKanbanFieldResource::class,
        Core05SubstitutionResource::class,
        Core05UuidMutationResource::class,
    ]);
    Aura::setModel(new Core05MutationResource);
    Gate::policy(Core05UuidMutationResource::class, Core05UuidMutationPolicy::class);
});

test('bulk modal resolves its component from the declared action and authorizes the exact selection', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Authorized modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $request = null;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertDispatched('openModal', function (string $event, array $parameters) use (&$request): bool {
            $request = $parameters[0] ?? null;

            return is_string($request)
                && ! str_contains($request, 'core05-authorized-bulk-modal');
        });

    expect($request)->toBeString();

    livewire(Modals::class)
        ->call('openModal', $request)
        ->assertSee('Authorized bulk modal');

    livewire(Modals::class)
        ->call('openModal', substr($request, 0, -1).'x')
        ->assertStatus(422);

    $this->actingAs(createSuperAdmin());

    livewire(Modals::class)
        ->call('openModal', $request)
        ->assertStatus(422);

    expect(Core05AuthorizedBulkModal::$mounts)->toBe(1)
        ->and(Core05ForgedModal::$mounts)->toBe(0);
});

test('bulk modal requests are single use for the same actor and team', function () {
    $this->actingAs(createSuperAdmin());
    $resource = Core05MutationResource::create([
        'title' => 'Single-use modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $request = null;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertDispatched('openModal', function (string $event, array $parameters) use (&$request): bool {
            $request = $parameters[0] ?? null;

            return is_string($request);
        });

    $modals = livewire(Modals::class);
    $modals->call('openModal', $request)->assertSee('Authorized bulk modal');
    $modals->call('openModal', $request)->assertStatus(422);

    expect(Core05AuthorizedBulkModal::$mounts)->toBe(1);
});

test('bulk modal redemption re-enters the issuing team context', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team context is only applicable when teams are enabled.');
    }

    $actor = createAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);
    $resource = Core05MutationResource::create([
        'title' => 'Context-bound modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $request = null;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertDispatched('openModal', function (string $event, array $parameters) use (&$request): bool {
            $request = $parameters[0] ?? null;

            return is_string($request);
        });

    Core05MutationBoundaryPolicy::$teamContexts = [];

    livewire(Modals::class)
        ->call('openModal', $request)
        ->assertSee('Authorized bulk modal');

    expect(Core05MutationBoundaryPolicy::$teamContexts)
        ->not->toBeEmpty()
        ->each->toBe($actor->current_team_id);
});

test('concurrent bulk modal redemption has exactly one atomic winner', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the concurrent modal redemption probe.');
    }

    $this->actingAs(createSuperAdmin());
    $resource = Core05MutationResource::create([
        'title' => 'Concurrent modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $request = null;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertDispatched('openModal', function (string $event, array $parameters) use (&$request): bool {
            $request = $parameters[0] ?? null;

            return is_string($request);
        });

    $children = [];

    foreach (range(1, 2) as $index) {
        $signals = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($signals === false) {
            $this->fail('Unable to create a modal redemption signal channel.');
        }

        $child = pcntl_fork();

        if ($child === -1) {
            $this->fail('Unable to fork the modal redemption probe.');
        }

        if ($child === 0) {
            fclose($signals[0]);
            fread($signals[1], 1);

            try {
                app(SignedModalRequest::class)->resolve($request);
                fwrite($signals[1], 'resolved');
                exit(0);
            } catch (Throwable $exception) {
                fwrite($signals[1], $exception instanceof HttpExceptionInterface
                    ? 'http:'.$exception->getStatusCode()
                    : $exception::class);
                exit(0);
            }
        }

        fclose($signals[1]);
        $children[] = ['pid' => $child, 'signal' => $signals[0]];
    }

    foreach ($children as $child) {
        fwrite($child['signal'], '1');
    }

    $results = [];

    foreach ($children as $child) {
        pcntl_waitpid($child['pid'], $status);
        $results[] = stream_get_contents($child['signal']);
        fclose($child['signal']);

        expect(pcntl_wifexited($status))->toBeTrue()
            ->and(pcntl_wexitstatus($status))->toBe(0);
    }

    expect($results)->toHaveCount(2)
        ->and(collect($results)->filter(fn (string $result): bool => $result === 'resolved'))->toHaveCount(1)
        ->and(collect($results)->filter(fn (string $result): bool => $result === 'http:422'))->toHaveCount(1);
});

test('bulk modal requests reject a changed team before consuming the request', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team-bound modal requests apply only when teams are enabled.');
    }

    $actor = createSuperAdmin();
    $this->actingAs($actor);
    $resource = Core05MutationResource::create([
        'title' => 'Team-bound modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $request = null;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertDispatched('openModal', function (string $event, array $parameters) use (&$request): bool {
            $request = $parameters[0] ?? null;

            return is_string($request);
        });

    $actor->forceFill(['current_team_id' => ((int) $actor->current_team_id) + 999]);

    livewire(Modals::class)->call('openModal', $request)->assertStatus(422);

    expect(Core05AuthorizedBulkModal::$mounts)->toBe(0);
});

test('bulk modal issuance rejects a process-local cache store', function () {
    $this->actingAs(createSuperAdmin());
    config()->set('aura.security.modal_requests.cache_store', 'array');
    $resource = Core05MutationResource::create([
        'title' => 'Unshared cache target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertStatus(503)
        ->assertNotDispatched('openModal');
});

test('bulk modal redemption rejects expiry and stale policy or scope membership', function (string $staleState) {
    $this->actingAs(createSuperAdmin());
    $resource = Core05MutationResource::create([
        'title' => 'Fresh modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $request = null;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertDispatched('openModal', function (string $event, array $parameters) use (&$request): bool {
            $request = $parameters[0] ?? null;

            return is_string($request);
        });

    match ($staleState) {
        'expiry' => $this->travel(2)->minutes(),
        'policy' => Gate::policy(Core05MutationResource::class, Core05DenyingMutationPolicy::class),
        'resource' => Aura::setModel(new Core05MediaResource),
        'scope' => $resource->forceFill(['title' => 'Excluded by indexQuery'])->save(),
    };

    livewire(Modals::class)
        ->call('openModal', $request)
        ->assertStatus($staleState === 'policy' ? 403 : 422);

    expect(Core05AuthorizedBulkModal::$mounts)->toBe(0);
})->with(['expiry', 'policy', 'resource', 'scope']);

test('bulk modal rejects forged action component parameters and records', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Forged modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'forgedAction')
        ->assertForbidden()
        ->assertNotDispatched('openModal');

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey(), PHP_INT_MAX])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertHasErrors(['selected'])
        ->assertNotDispatched('openModal');

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal', [
            'modal' => 'core05-forged-modal',
            'selected' => [PHP_INT_MAX],
            'model' => Core05ForgedModal::class,
        ])
        ->assertDispatched('openModal', function (string $event, array $parameters): bool {
            $request = $parameters[0] ?? null;

            return is_string($request)
                && ! str_contains($request, 'core05-forged-modal')
                && ! str_contains($request, (string) PHP_INT_MAX);
        });

    expect(Core05ForgedModal::$mounts)->toBe(0);
});

test('bulk modal rejects denied and cross-team records before opening', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    $resource = Core05MutationResource::create([
        'title' => 'Denied modal target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    Gate::policy(Core05MutationResource::class, Core05DenyingMutationPolicy::class);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$resource->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertForbidden()
        ->assertNotDispatched('openModal');

    if (! config('aura.teams')) {
        return;
    }

    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);
    $foreignTeam = foreignTeam();
    $foreignAttributes = [
        'title' => 'Foreign modal target',
        'content' => 'unchanged',
        'status' => 'draft',
        'user_id' => $foreignTeam->user_id,
    ];

    expect(fn () => Core05MutationResource::withoutGlobalScopes()->create([
        ...$foreignAttributes,
        'team_id' => $foreignTeam->getKey(),
    ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $foreign = Core05MutationResource::createForTeamForSystem(
        $foreignTeam->getKey(),
        $foreignAttributes,
        $actor->getConnection(),
    );

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [$foreign->getKey()])
        ->call('openBulkActionModal', 'openReviewModal')
        ->assertHasErrors(['selected'])
        ->assertNotDispatched('openModal');
});

test('global modal manager rejects forged component names through calls and events', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    $resource = Core05MutationResource::create([
        'title' => 'Global modal authorization target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Modals::class)
        ->call('openModal', 'core05-forged-modal')
        ->assertStatus(422)
        ->assertDontSee('Forged modal');

    livewire(Modals::class)
        ->dispatch('openModal', 'core05-forged-modal')
        ->assertStatus(422)
        ->assertDontSee('Forged modal');

    livewire(Modals::class)
        ->call('openModal', 'aura::resource-view-modal', [
            'type' => Core05MutationResource::$slug,
            'resource' => $resource->getKey(),
            'component' => 'core05-forged-modal',
        ])
        ->assertStatus(422);

    livewire(Modals::class)
        ->dispatch('openModal', component: 'aura::resource-view-modal', arguments: [
            'type' => Core05MutationResource::$slug,
            'resource' => PHP_INT_MAX,
        ])
        ->assertNotFound();

    if (config('aura.teams')) {
        $foreignTeam = foreignTeam();
        $foreignAttributes = [
            'title' => 'Foreign global modal target',
            'content' => 'unchanged',
            'status' => 'draft',
            'user_id' => $foreignTeam->user_id,
        ];

        expect(fn () => Core05MutationResource::withoutGlobalScopes()->create([
            ...$foreignAttributes,
            'team_id' => $foreignTeam->getKey(),
        ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

        $foreign = Core05MutationResource::createForTeamForSystem(
            $foreignTeam->getKey(),
            $foreignAttributes,
            $actor->getConnection(),
        );

        livewire(Modals::class)
            ->call('openModal', 'aura::resource-view-modal', [
                'type' => Core05MutationResource::$slug,
                'resource' => $foreign->getKey(),
            ])
            ->assertNotFound();
    }

    expect(Core05ForgedModal::$mounts)->toBe(0);
});

test('global modal state cannot be hydrated with component definitions or nested parameters', function () {
    $this->actingAs(createSuperAdmin());
    $payload = [
        'forged' => [
            'name' => 'core05-forged-modal',
            'arguments' => [],
            'modalAttributes' => [
                'persistent' => false,
                'modalClasses' => 'max-w-4xl',
                'slideOver' => false,
            ],
        ],
    ];

    expect(fn () => livewire(Modals::class)->set('modals', $payload))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => livewire(Modals::class)->set('modals.forged.name', 'core05-forged-modal'))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    livewire(Modals::class)
        ->set('activeModals.forged', true)
        ->assertForbidden()
        ->assertDontSee('Forged modal');

    expect(Core05ForgedModal::$mounts)->toBe(0);
});

test('media manager rejects arbitrary container bindings before resolving them', function () {
    $this->actingAs(createSuperAdmin());

    livewire(Modals::class)
        ->call('openModal', 'aura::media-manager', [
            'resource' => Core05DangerousContainerBinding::class,
            'slug' => 'hero_image',
            'selected' => [],
        ])
        ->assertStatus(422);

    expect(Core05DangerousContainerBinding::$constructions)->toBe(0);
});

test('media manager safely preserves the documented legacy model argument', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);

    livewire(Modals::class)
        ->call('openModal', 'aura::media-manager', [
            'model' => Core05MediaResource::class,
            'slug' => 'hero_image',
            'selected' => [],
        ])
        ->assertSeeHtml('data-media-picker-root');

    $component = livewire(MediaManager::class, [
        'model' => Core05MediaResource::class,
        'slug' => 'hero_image',
        'selected' => [],
        'modalAttributes' => [],
    ])
        ->assertSet('resource', Core05MediaResource::$slug)
        ->assertSet('model', Core05MediaResource::class)
        ->assertOk();

    $component
        ->call('tableMounted')
        ->assertSet('resource', Core05MediaResource::$slug)
        ->assertSet('model', Core05MediaResource::class);
});

test('media manager exposes a locked canonical model for resource mounts and hydrated APIs', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    $component = livewire(MediaManager::class, [
        'resource' => Core05MediaResource::$slug,
        'slug' => 'hero_image',
        'selected' => [],
        'modalAttributes' => [],
    ])
        ->assertSet('resource', Core05MediaResource::$slug)
        ->assertSet('model', Core05MediaResource::class);

    expect(fn () => $component->set('model', Core05MutationResource::class))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    $component
        ->call('select', [])
        ->assertSet('resource', Core05MediaResource::$slug)
        ->assertSet('model', Core05MediaResource::class);
});

test('resource create rejects forged media field updates before mutating public form state', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);

    livewire(Create::class, ['slug' => Core05MediaResource::$slug])
        ->call('updateField', [
            'slug' => 'forged_media',
            'value' => [PHP_INT_MAX],
        ])
        ->assertStatus(422)
        ->assertSet('form.fields.forged_media', null);
});

test('resource create validates direct public media state before persistence', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    $before = Core05MediaResource::query()->count();

    livewire(Create::class, ['slug' => Core05MediaResource::$slug])
        ->set('form.fields.hero_image', [PHP_INT_MAX])
        ->call('save')
        ->assertStatus(422);

    expect(Core05MediaResource::query()->count())->toBe($before);
});

test('media field updates preserve explicit empty values and accept owned image and file attachments', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    $attachment = Attachment::create(['title' => 'Owned parent field attachment']);
    $component = livewire(Create::class, ['slug' => Core05MediaResource::$slug]);

    foreach ([null, '', []] as $emptyValue) {
        $component
            ->call('updateField', ['slug' => 'hero_image', 'value' => $emptyValue])
            ->assertSet('form.fields.hero_image', $emptyValue);
    }

    $component
        ->call('updateField', ['slug' => 'hero_image', 'value' => [$attachment->getKey()]])
        ->assertSet('form.fields.hero_image', [$attachment->getKey()])
        ->call('updateField', ['slug' => 'document', 'value' => [$attachment->getKey()]])
        ->assertSet('form.fields.document', [$attachment->getKey()]);
});

test('explicit empty media values do not require attachment access', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    Gate::policy(Attachment::class, Core05DenyAttachmentPolicy::class);
    $before = Core05MediaResource::query()->count();

    livewire(Create::class, ['slug' => Core05MediaResource::$slug])
        ->call('updateField', ['slug' => 'hero_image', 'value' => null])
        ->assertOk();

    livewire(Create::class, ['slug' => Core05MediaResource::$slug])
        ->call('save')
        ->assertHasNoErrors();

    expect(Core05MediaResource::query()->count())->toBe($before + 1);
});

test('parent media field updates reject attachments outside the current team scope', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This test exercises team-only behavior.');
    }

    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Aura::setModel(new Core05MediaResource);
    $foreignTeam = foreignTeam();
    $foreignAttributes = [
        'title' => 'Foreign parent field attachment',
        'user_id' => $foreignTeam->user_id,
    ];

    expect(fn () => Attachment::withoutGlobalScopes()->create([
        ...$foreignAttributes,
        'team_id' => $foreignTeam->getKey(),
    ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $attachment = Attachment::createForTeamForSystem(
        $foreignTeam->getKey(),
        $foreignAttributes,
        $actor->getConnection(),
    );

    livewire(Create::class, ['slug' => Core05MediaResource::$slug])
        ->call('updateField', [
            'slug' => 'hero_image',
            'value' => [$attachment->getKey()],
        ])
        ->assertStatus(422)
        ->assertSet('form.fields.hero_image', null);
});

test('resource edit validates declared file state before persistence', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    $resource = Core05MediaResource::create([
        'fields' => [
            'hero_image' => null,
            'document' => null,
        ],
    ]);

    livewire(Edit::class, [
        'id' => $resource->getKey(),
        'slug' => Core05MediaResource::$slug,
    ])
        ->set('form.fields.document', [PHP_INT_MAX])
        ->call('save')
        ->assertStatus(422);

    expect($resource->fresh()->document)->toBeNull();
});

test('settings validates direct logo state before persistence', function () {
    $this->actingAs(createSuperAdmin());
    $component = livewire(Settings::class);
    $settings = $component->get('model');
    $originalValue = $settings->value;

    $component
        ->set('form.fields.logo', [PHP_INT_MAX])
        ->call('save')
        ->assertStatus(422);

    expect($settings->fresh()->value)->toBe($originalValue);
});

test('profile validates direct avatar state before persistence', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    $originalAvatar = $actor->avatar;

    livewire(Profile::class)
        ->set('form.fields.avatar', [PHP_INT_MAX])
        ->call('save')
        ->assertStatus(422);

    expect($actor->fresh()->avatar)->toBe($originalAvatar);
});

test('profile validates media updates at the public event boundary', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    livewire(Profile::class)
        ->call('updateField', [
            'slug' => 'avatar',
            'value' => [PHP_INT_MAX],
        ])
        ->assertStatus(422)
        ->assertSet('form.fields.avatar', $actor->avatar);
});

test('legacy media manager model input never resolves an unregistered container class', function () {
    $this->actingAs(createSuperAdmin());

    livewire(Modals::class)
        ->call('openModal', 'aura::media-manager', [
            'model' => Core05DangerousContainerBinding::class,
            'slug' => 'hero_image',
            'selected' => [],
        ])
        ->assertStatus(422);

    expect(Core05DangerousContainerBinding::$constructions)->toBe(0);
});

test('media manager resolves only an owned media field on an authorized registered resource', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);

    livewire(Modals::class)
        ->call('openModal', 'aura::media-manager', [
            'resource' => Core05MediaResource::$slug,
            'slug' => 'hero_image',
            'selected' => [],
        ])
        ->assertSeeHtml('data-media-picker-root');

    livewire(Modals::class)
        ->call('openModal', 'aura::media-manager', [
            'resource' => Core05MutationResource::$slug,
            'slug' => 'status',
            'selected' => [],
        ])
        ->assertStatus(422);
});

test('media manager authorizes the registered resource before mounting', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    Gate::policy(Core05MediaResource::class, Core05DenyMediaPolicy::class);

    livewire(Modals::class)
        ->call('openModal', 'aura::media-manager', [
            'resource' => Core05MediaResource::$slug,
            'slug' => 'hero_image',
            'selected' => [],
        ])
        ->assertForbidden();
});

test('media manager enforces its resource and field boundary when mounted directly', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);

    livewire(MediaManager::class, [
        'resource' => Core05MutationResource::$slug,
        'slug' => 'status',
        'selected' => [],
        'modalAttributes' => [],
    ])->assertStatus(422);

    Gate::policy(Core05MediaResource::class, Core05DenyMediaPolicy::class);

    livewire(MediaManager::class, [
        'resource' => Core05MediaResource::$slug,
        'slug' => 'hero_image',
        'selected' => [],
        'modalAttributes' => [],
    ])->assertForbidden();
});

test('media manager reauthorizes hydrated actions against current policy state', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    $component = livewire(MediaManager::class, [
        'resource' => Core05MediaResource::$slug,
        'slug' => 'hero_image',
        'selected' => [],
        'modalAttributes' => [],
    ])->assertOk();

    Gate::policy(Core05MediaResource::class, Core05DenyMediaPolicy::class);

    $component->call('select', [])->assertForbidden();
});

test('media manager enforces the attachment policy at its component boundary', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    Gate::policy(Attachment::class, Core05DenyAttachmentPolicy::class);

    livewire(MediaManager::class, [
        'resource' => Core05MediaResource::$slug,
        'slug' => 'hero_image',
        'selected' => [],
        'modalAttributes' => [],
    ])->assertForbidden();
});

test('media manager rejects selected records outside the current team scope', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This test exercises team-only behavior.');
    }

    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Aura::setModel(new Core05MediaResource);
    $foreignTeam = foreignTeam();
    $foreignAttributes = [
        'title' => 'Foreign attachment',
        'user_id' => $foreignTeam->user_id,
    ];

    expect(fn () => Attachment::withoutGlobalScopes()->create([
        ...$foreignAttributes,
        'team_id' => $foreignTeam->getKey(),
    ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $attachment = Attachment::createForTeamForSystem(
        $foreignTeam->getKey(),
        $foreignAttributes,
        $actor->getConnection(),
    );

    livewire(MediaManager::class, [
        'resource' => Core05MediaResource::$slug,
        'slug' => 'hero_image',
        'selected' => [$attachment->getKey()],
        'modalAttributes' => [],
    ])->assertStatus(422);
});

test('nested media uploader reauthorizes a revoked resource on every hydrated child request', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    Storage::fake('public');
    Gate::policy(Core05MediaResource::class, Core05ToggleMediaPolicy::class);
    $field = (new Core05MediaResource)->fieldBySlug('hero_image');
    $component = livewire(MediaUploader::class, [
        'resource' => Core05MediaResource::$slug,
        'fieldSlug' => 'hero_image',
        'field' => $field,
        'selected' => [],
        'table' => true,
    ])->assertOk();

    Core05ToggleMediaPolicy::$allowed = false;

    $component
        ->call('selectedMediaUpdated', ['slug' => 'hero_image', 'value' => []])
        ->assertForbidden();

    expect(Attachment::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing('media/revoked.png');
});

test('nested media uploader authorizes attachment creation before storage or database writes', function () {
    $this->actingAs(createSuperAdmin());
    Aura::setModel(new Core05MediaResource);
    Gate::policy(Attachment::class, Core05DenyAttachmentCreatePolicy::class);
    Storage::fake('public');

    livewire(MediaUploader::class, [
        'resource' => Core05MediaResource::$slug,
        'fieldSlug' => 'hero_image',
        'field' => (new Core05MediaResource)->fieldBySlug('hero_image'),
        'selected' => [],
        'table' => true,
    ])
        ->set('media', [UploadedFile::fake()->image('denied.png')])
        ->assertForbidden();

    expect(Attachment::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing('media/denied.png');
});

test('nested media uploader rejects forged and cross-team picker state', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Aura::setModel(new Core05MediaResource);

    livewire(MediaUploader::class, [
        'resource' => Core05MutationResource::$slug,
        'fieldSlug' => 'status',
        'field' => (new Core05MutationResource)->fieldBySlug('status'),
        'selected' => [],
        'table' => true,
    ])->assertStatus(422);

    if (! config('aura.teams')) {
        return;
    }

    $foreignTeam = foreignTeam();
    $foreignAttributes = [
        'title' => 'Foreign picker attachment',
        'user_id' => $foreignTeam->user_id,
    ];

    expect(fn () => Attachment::withoutGlobalScopes()->create([
        ...$foreignAttributes,
        'team_id' => $foreignTeam->getKey(),
    ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $attachment = Attachment::createForTeamForSystem(
        $foreignTeam->getKey(),
        $foreignAttributes,
        $actor->getConnection(),
    );

    livewire(MediaUploader::class, [
        'resource' => Core05MediaResource::$slug,
        'fieldSlug' => 'hero_image',
        'field' => (new Core05MediaResource)->fieldBySlug('hero_image'),
        'selected' => [$attachment->getKey()],
        'table' => true,
    ])->assertStatus(422);
});

test('bulk mutations fail closed before an explicit or select-all selection exceeds the configured bound', function (
    bool $selectAll,
) {
    $this->actingAs(createSuperAdmin());
    config()->set('aura.security.table_mutations.max_records', 2);
    $resources = collect(range(1, 3))->map(fn (int $number) => Core05MutationResource::create([
        'title' => 'Bounded target '.$number,
        'content' => 'unchanged',
        'status' => 'draft',
    ]));
    $component = livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource]);

    if ($selectAll) {
        $component->set('selectAll', true);
    } else {
        $component->set('selected', $resources->pluck('id')->all());
    }

    $component->call('bulkAction', 'markBulkReviewed')->assertHasErrors(['selected']);

    expect($resources->map->fresh()->pluck('content')->all())->each->toBe('unchanged');
})->with([
    'explicit selection' => false,
    'select all' => true,
]);

test('bulk mutations accept a currently available provider sort with exact decimal and primary-key ordering', function (
    bool $selectAll,
) {
    $this->actingAs(createSuperAdmin());
    $firstEquivalent = Core05MutationResource::create([
        'title' => 'First equivalent contextual amount',
        'content' => '2',
        'status' => 'draft',
    ]);
    $secondEquivalent = Core05MutationResource::create([
        'title' => 'Second equivalent contextual amount',
        'content' => '2.00',
        'status' => 'draft',
    ]);
    $higher = Core05MutationResource::create([
        'title' => 'Higher contextual amount',
        'content' => '10',
        'status' => 'draft',
    ]);
    Aura::registerFieldProvider(
        Core05ContextualSortFieldProvider::class,
        resources: [Core05MutationResource::class],
    );

    $component = livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('sorts', ['content' => 'asc']);

    if ($selectAll) {
        $component->set('selectAll', true);
    } else {
        $component->set('selected', [
            $firstEquivalent->getKey(),
            $secondEquivalent->getKey(),
            $higher->getKey(),
        ]);
    }

    $component
        ->call('bulkCollectionAction', 'captureCollectionOrder')
        ->assertHasNoErrors();

    expect(Core05MutationResource::$capturedCollectionIdChunks)->toBe([[
        $secondEquivalent->getKey(),
        $firstEquivalent->getKey(),
        $higher->getKey(),
    ]]);
})->with([
    'explicit provider-sorted selection' => false,
    'select-all provider-sorted selection' => true,
]);

test('bulk mutations qualify a contextual physical number sort across joined scopes', function (bool $selectAll) {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    $firstEquivalent = Core05MutationResource::create([
        'title' => 'First joined contextual amount',
        'content' => '2',
        'status' => 'draft',
    ]);
    $secondEquivalent = Core05MutationResource::create([
        'title' => 'Second joined contextual amount',
        'content' => '2.00',
        'status' => 'draft',
    ]);
    $higher = Core05MutationResource::create([
        'title' => 'Higher joined contextual amount',
        'content' => '10',
        'status' => 'draft',
    ]);
    $resources = collect([$firstEquivalent, $secondEquivalent, $higher]);
    $resources->each(fn (Core05MutationResource $resource) => core05CreateMutationCollision($resource, $actor));
    Aura::registerFieldProvider(
        Core05ContextualSortFieldProvider::class,
        resources: [Core05MutationResource::class],
    );

    $component = livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('sorts', ['content' => 'asc']);

    if ($selectAll) {
        $component->set('selectAll', true);
    } else {
        $component->set('selected', $resources->pluck('id')->all());
    }

    $component
        ->call('bulkCollectionAction', 'captureCollectionOrder')
        ->assertHasNoErrors();

    expect(Core05MutationResource::$capturedCollectionIdChunks)->toBe([[
        $secondEquivalent->getKey(),
        $firstEquivalent->getKey(),
        $higher->getKey(),
    ]]);
})->with([
    'explicit joined provider-sorted selection' => false,
    'select-all joined provider-sorted selection' => true,
]);

test('chunked bulk mutations preserve one global contextual display order without duplicates', function (bool $selectAll) {
    $this->actingAs(createSuperAdmin());
    config()->set('aura.security.table_mutations.chunk_size', 2);
    $resources = collect(['10', '2.0', '1', '02.000', '-1', '2'])->map(fn (string $amount) => Core05MutationResource::create([
        'title' => 'Chunked contextual amount '.$amount,
        'content' => (string) $amount,
        'status' => 'draft',
    ]));
    Aura::registerFieldProvider(
        Core05ContextualSortFieldProvider::class,
        resources: [Core05MutationResource::class],
    );

    $component = livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('sorts', ['content' => 'asc']);

    if ($selectAll) {
        $component->set('selectAll', true);
    } else {
        $component->set('selected', $resources->pluck('id')->all());
    }

    $component
        ->call('bulkCollectionAction', 'captureCollectionOrder')
        ->assertHasNoErrors();

    $capturedIds = collect(Core05MutationResource::$capturedCollectionIdChunks)->flatten()->all();
    $expectedIds = collect([4, 2, 5, 3, 1, 0])
        ->map(fn (int $index): mixed => $resources[$index]->getKey())
        ->all();

    expect(Core05MutationResource::$capturedCollectionIdChunks)->each->toHaveCount(2)
        ->and($capturedIds)->toBe($expectedIds)
        ->and(array_values(array_unique($capturedIds)))->toBe($capturedIds);
})->with([
    'explicit chunked provider-sorted selection' => false,
    'select-all chunked provider-sorted selection' => true,
]);

test('maximum explicit contextual selection has a bounded query budget and remains globally ordered', function () {
    $this->actingAs(createSuperAdmin());
    config()->set('aura.security.table_mutations.max_records', 500);
    config()->set('aura.security.table_mutations.chunk_size', 100);
    $resources = collect(range(1, 500))->map(fn (int $amount) => Core05MutationResource::create([
        'title' => 'Maximum contextual amount '.$amount,
        'content' => (string) (501 - $amount),
        'status' => 'draft',
    ]));
    Aura::registerFieldProvider(
        Core05ContextualSortFieldProvider::class,
        resources: [Core05MutationResource::class],
    );
    $table = new Table;
    $table->model = new Core05MutationResource;
    $table->sorts = ['content' => 'asc'];
    $scope = $table->rowsQuery();
    $connection = DB::connection();
    $originalGrammar = $connection->getQueryGrammar();

    $connection->setQueryGrammar(new Core05LockObservingSQLiteGrammar($connection));
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        app(TableMutationDispatcher::class)->dispatchBulk(
            $scope,
            new TableMutationModelDescriptor($table->model),
            'captureCollectionOrder',
            $table->model->getBulkActions(),
            $resources->pluck('id')->all(),
            false,
            'collection',
        );
        $queries = $connection->getQueryLog();
    } finally {
        $connection->disableQueryLog();
        $connection->flushQueryLog();
        $connection->setQueryGrammar($originalGrammar);
    }

    $capturedIds = collect(Core05MutationResource::$capturedCollectionIdChunks)->flatten()->all();
    $expectedIds = $resources->reverse()->pluck('id')->values()->all();
    $lockingQueries = collect($queries)->filter(
        fn (array $query): bool => str_contains($query['query'], '/* core05-lock-for-update */'),
    );

    expect(count($queries))->toBeLessThanOrEqual(40)
        ->and($lockingQueries)->toHaveCount(5)
        ->and($capturedIds)->toBe($expectedIds)
        ->and($capturedIds)->toHaveCount(500)
        ->and(array_values(array_unique($capturedIds)))->toBe($capturedIds);
});

test('bulk mutations reject a provider sort withdrawn after selection without writing', function (bool $selectAll) {
    $this->actingAs(createSuperAdmin());
    $resources = collect(range(1, 3))->map(fn (int $amount) => Core05MutationResource::create([
        'title' => 'Withdrawn contextual amount '.$amount,
        'content' => (string) $amount,
        'status' => 'draft',
    ]));
    Aura::registerFieldProvider(
        Core05ContextualSortFieldProvider::class,
        resources: [Core05MutationResource::class],
    );

    $component = livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('sorts', ['content' => 'asc']);

    if ($selectAll) {
        $component->set('selectAll', true);
    } else {
        $component->set('selected', $resources->pluck('id')->all());
    }

    Core05ContextualSortFieldState::$available = false;

    $component
        ->call('bulkCollectionAction', 'captureCollectionOrder')
        ->assertStatus(422);

    $storedContent = DB::table('posts')
        ->whereIn('id', $resources->pluck('id'))
        ->orderBy('id')
        ->pluck('content')
        ->all();

    expect(Core05MutationResource::$capturedCollectionIdChunks)->toBe([])
        ->and($storedContent)->toBe(['1', '2', '3']);
})->with([
    'explicit selection after provider withdrawal' => false,
    'select all after provider withdrawal' => true,
]);

test('large client selections fail before creating an oversized parameter list', function () {
    $this->actingAs(createSuperAdmin());
    config()->set('aura.security.table_mutations.max_records', 500);
    Core05MutationResource::$updateInvocations = 0;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', range(1, 501))
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasErrors(['selected']);

    expect(Core05MutationResource::$updateInvocations)->toBe(0);
});

test('select all bulk mutations use the exact searched display scope', function () {
    $this->actingAs(createSuperAdmin());
    $matching = Core05MutationResource::create([
        'title' => 'Needle target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $outside = Core05MutationResource::create([
        'title' => 'Outside target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('search', 'Needle')
        ->set('selectAll', true)
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasNoErrors();

    expect($matching->fresh()->content)->toBe('reviewed-by-bulk-action')
        ->and($outside->fresh()->content)->toBe('unchanged');
});

test('select all bulk mutations honor filters and validated exclusions', function () {
    $this->actingAs(createSuperAdmin());
    $included = Core05MutationResource::create([
        'title' => 'Included draft',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $excluded = Core05MutationResource::create([
        'title' => 'Excluded draft',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $filteredOut = Core05MutationResource::create([
        'title' => 'Reviewed row',
        'content' => 'unchanged',
        'status' => 'reviewed',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'status',
                'operator' => 'is',
                'value' => 'draft',
                'main_operator' => 'and',
            ]],
        ]])
        ->set('selectAll', true)
        ->set('selectAllExclusions', [$excluded->getKey()])
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasNoErrors();

    expect($included->fresh()->content)->toBe('reviewed-by-bulk-action')
        ->and($excluded->fresh()->content)->toBe('unchanged')
        ->and($filteredOut->fresh()->content)->toBe('unchanged');
});

test('select all interaction keeps exact scoped mode while rows are excluded and reselected', function () {
    $this->actingAs(createSuperAdmin());
    $included = Core05MutationResource::create([
        'title' => 'Needle included',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $excluded = Core05MutationResource::create([
        'title' => 'Needle excluded',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    Core05MutationResource::create([
        'title' => 'Outside search',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    $component = livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('search', 'Needle')
        ->call('selectAllRows')
        ->assertSet('selectAll', true)
        ->assertSet('selected', [])
        ->assertSet('selectAllExclusions', []);

    $component
        ->call('updateRowSelection', [$excluded->getKey()], false)
        ->assertSet('selectAll', true)
        ->assertSet('selectAllExclusions', [$excluded->getKey()])
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasNoErrors();

    expect($included->fresh()->content)->toBe('reviewed-by-bulk-action')
        ->and($excluded->fresh()->content)->toBe('unchanged');

    $component
        ->set('search', 'Needle')
        ->call('selectAllRows')
        ->call('updateRowSelection', [$excluded->getKey()], false)
        ->call('updateRowSelection', [$excluded->getKey()], true)
        ->assertSet('selectAll', true)
        ->assertSet('selectAllExclusions', []);
});

test('effective scope changes reset an active select all lifecycle', function (string $property, mixed $value) {
    $this->actingAs(createSuperAdmin());
    Core05MutationResource::create([
        'title' => 'Lifecycle target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('selectAllRows')
        ->call('updateRowSelection', [Core05MutationResource::query()->value('id')], false)
        ->set($property, $value)
        ->assertSet('selectAll', false)
        ->assertSet('selectAllExclusions', []);
})->with([
    'search' => ['search', 'changed'],
    'custom filter' => ['filters.custom', [[
        'filters' => [[
            'name' => 'status',
            'operator' => 'is',
            'value' => 'draft',
            'main_operator' => 'and',
        ]],
    ]]],
    'quick filter' => ['quickFilters', ['type' => 'image']],
]);

test('select all bulk mutations reject forged exclusions outside the effective scope', function () {
    $this->actingAs(createSuperAdmin());
    $visible = Core05MutationResource::create([
        'title' => 'Visible draft',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $outside = Core05MutationResource::create([
        'title' => 'Filtered out',
        'content' => 'unchanged',
        'status' => 'reviewed',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'status',
                'operator' => 'is',
                'value' => 'draft',
                'main_operator' => 'and',
            ]],
        ]])
        ->set('selectAll', true)
        ->set('selectAllExclusions', [$outside->getKey()])
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasErrors(['selected']);

    expect($visible->fresh()->content)->toBe('unchanged')
        ->and($outside->fresh()->content)->toBe('unchanged');
});

test('select all bulk mutations fail closed for an undeclared filter operator', function () {
    $this->actingAs(createSuperAdmin());
    $resource = Core05MutationResource::create([
        'title' => 'Protected row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('filters.custom', [[
            'filters' => [[
                'name' => 'status',
                'operator' => 'forged_operator',
                'value' => 'draft',
            ]],
        ]])
        ->set('selectAll', true)
        ->call('bulkAction', 'markBulkReviewed')
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('select all bulk mutations honor a saved filter loaded from query string state', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    $actor->updateOption('Core05Mutation.filters.only-draft', [
        'custom' => [[
            'filters' => [[
                'name' => 'status',
                'operator' => 'is',
                'value' => 'draft',
            ]],
        ]],
        'name' => 'Only draft',
        'public' => false,
        'global' => false,
        'slug' => 'only-draft',
    ]);
    $included = Core05MutationResource::create([
        'title' => 'Draft row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $outside = Core05MutationResource::create([
        'title' => 'Reviewed row',
        'content' => 'unchanged',
        'status' => 'reviewed',
    ]);

    Livewire::withQueryParams(['selectedFilter' => 'only-draft'])
        ->test(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selectAll', true)
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasNoErrors();

    expect($included->fresh()->content)->toBe('reviewed-by-bulk-action')
        ->and($outside->fresh()->content)->toBe('unchanged');
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
    expect(auth()->id())->toBe($actor->getKey());

    $resource = Core05MutationResource::create([
        'title' => 'Authoritative title',
        'content' => 'authoritative-content',
        'status' => 'draft',
    ]);
    $foreignOwner = User::factory()->create();

    $attributes = [
        'data' => 'authoritative-data',
    ];

    expect(fn () => $resource->forceFill([
        ...$attributes,
        'user_id' => $foreignOwner->getKey(),
    ])->save())->toThrow(
        LogicException::class,
        'A resource owner must match the authenticated actor or an explicit named system operation.',
    );

    $resource->refresh();
    $resource->forceFill($attributes);
    expect($resource->assignOwnerForSystem($foreignOwner->getKey()))->toBeTrue();

    return $resource->refresh();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function core05CreateMutationResourceOnSecondaryConnection(array $attributes): Core05MutationResource
{
    $actor = auth()->user();
    expect($actor)->toBeInstanceOf(User::class);

    $attributes['user_id'] ??= $actor->getKey();
    $secondary = DB::connection('core05_mutation_secondary');
    $model = (new Core05MutationResource)->setConnection($secondary->getName());

    expect(fn () => $model->newQuery()->create($attributes))
        ->toThrow(LogicException::class, 'authenticated actor and resource must use the same database connection');

    if (config('aura.teams')) {
        return Core05MutationResource::createForTeamForSystem(
            $attributes['team_id'] ?? $actor->current_team_id,
            $attributes,
            $secondary,
        );
    }

    return Core05MutationResource::createForOwnerForSystem(
        $attributes['user_id'],
        $attributes,
        $secondary,
    );
}

function core05AuthenticateActorOnSecondaryConnection(User $actor): User
{
    $secondary = DB::connection('core05_mutation_secondary');
    $secondary->table('users')->insert([
        'id' => $actor->getKey(),
        'current_team_id' => $actor->current_team_id,
    ]);

    $secondaryActor = clone $actor;
    $secondaryActor->setConnection($secondary->getName());
    auth()->setUser($secondaryActor);

    return $secondaryActor;
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
            $target = core05CreateMutationResourceOnSecondaryConnection($attributes);

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
        'bulk record select all' => livewire(Table::class, ['query' => $query, 'model' => $mounted])
            ->set('selectAll', true)
            ->call('bulkAction', 'markBulkReviewed'),
        'bulk collection select all' => livewire(Table::class, ['query' => $query, 'model' => $mounted])
            ->set('selectAll', true)
            ->call('bulkCollectionAction', 'captureCollectionAttributes'),
        'Kanban update' => livewire(Table::class, ['query' => $query, 'model' => $mounted])
            ->call('updateCardStatus', $id, 'reviewed'),
    };
}

/**
 * @return array<int, array{query: string, bindings: array<int, mixed>, time: float}>
 */
function core05CaptureLockedMutationQueries(Closure $callback): array
{
    $connection = DB::connection();
    $originalGrammar = $connection->getQueryGrammar();
    $queries = [];

    $connection->setQueryGrammar(new Core05LockObservingSQLiteGrammar($connection));
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        $callback();
        $queries = $connection->getQueryLog();
    } finally {
        $connection->disableQueryLog();
        $connection->flushQueryLog();
        $connection->setQueryGrammar($originalGrammar);
    }

    return array_values(array_filter(
        $queries,
        fn (array $query): bool => str_contains($query['query'], 'select "posts".* from "posts"')
            && str_contains($query['query'], '/* core05-lock-for-update */'),
    ));
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
    $this->actingAs($user);
    $user->roles()->firstOrFail()->update([
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
    $this->actingAs($user);
    $user->roles()->firstOrFail()->update([
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
    $this->actingAs($user);
    $user->roles()->firstOrFail()->update([
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
        ->assertStatus(422);

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
        ->assertStatus(422);

    expect($resource->fresh()->status)->toBe('draft');
});

test('table action cannot resolve a record from another team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation only applies when teams are enabled.');
    }

    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $otherTeam = foreignTeam();
    $foreignAttributes = [
        'title' => 'Other team',
        'content' => 'unchanged',
        'status' => 'draft',
        'user_id' => $otherTeam->user_id,
    ];

    expect(fn () => Core05MutationResource::withoutGlobalScopes()->create([
        ...$foreignAttributes,
        'team_id' => $otherTeam->getKey(),
    ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $foreignResource = Core05MutationResource::createForTeamForSystem(
        $otherTeam->getKey(),
        $foreignAttributes,
        $actor->getConnection(),
    );

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

    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $otherTeam = foreignTeam();
    $foreignAttributes = [
        'title' => 'Other team card',
        'status' => 'draft',
        'user_id' => $otherTeam->user_id,
    ];

    expect(fn () => Core05MutationResource::withoutGlobalScopes()->create([
        ...$foreignAttributes,
        'team_id' => $otherTeam->getKey(),
    ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $foreignResource = Core05MutationResource::createForTeamForSystem(
        $otherTeam->getKey(),
        $foreignAttributes,
        $actor->getConnection(),
    );

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

test('authoritative query callbacks cannot substitute or poison mutation records', function (
    string $surface,
    string $callback,
) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05AuthoritativeCallbackPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Authoritative callback target',
        'content' => 'authoritative-callback-content',
        'status' => 'draft',
    ]);

    DB::table('core05_mutation_substitutions')->insert([
        'id' => $resource->getKey(),
        'type' => Core05MutationResource::$type,
        'title' => 'Poisoned callback title',
        'content' => 'poisoned-callback-content',
        'status' => 'draft',
        'user_id' => $resource->user_id,
        'team_id' => $resource->team_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Core05MutationResource::$authoritativeQueryCallback = $callback;
    $result = core05CallMutationSurface(
        $surface,
        null,
        new Core05MutationResource,
        $resource->getKey(),
    );

    if ($callback === 'before-query table switch') {
        $result->assertStatus(422);

        expect(Core05MutationResource::$updateInvocations)->toBe(0)
            ->and($resource->fresh()->content)->toBe('authoritative-callback-content')
            ->and($resource->fresh()->status)->toBe('draft');

        return;
    }

    $result->assertHasNoErrors();

    expect(Core05AuthoritativeCallbackPolicy::$attempts)->toBeGreaterThanOrEqual(1)
        ->and(collect(Core05AuthoritativeCallbackPolicy::$snapshots)->contains(
            fn (array $snapshot): bool => $snapshot['title'] === 'Poisoned callback title'
                || $snapshot['content'] === 'poisoned-callback-content',
        ))->toBeFalse();

    $freshResource = $resource->fresh();

    match ($surface) {
        'single action' => expect($freshResource->content)->toBe('reviewed-by-action'),
        'bulk record' => expect($freshResource->content)->toBe('reviewed-by-bulk-action'),
        'bulk collection' => expect(
            json_decode($freshResource->content, true, flags: JSON_THROW_ON_ERROR)
        )->toMatchArray([
            'title' => 'Authoritative callback target',
            'content' => 'authoritative-callback-content',
            'status' => 'draft',
            'ids' => [$resource->getKey()],
        ]),
        'Kanban update' => expect($freshResource->status)->toBe('reviewed')
            ->and($freshResource->content)->toBe('authoritative-callback-content'),
    };
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
])->with([
    'before-query table switch' => 'before-query table switch',
    'after-query model injection' => 'after-query model injection',
]);

test('before-query callbacks cannot erase mandatory scope predicates', function (string $surface) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $foreignType = Core05NoKanbanFieldResource::create([
        'title' => 'Foreign callback target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::withoutGlobalScopes()->whereKey($foreignType->getKey())
    );

    Core05MutationResource::$authoritativeReadCallback = static function ($query) use ($foreignType): void {
        $query->wheres = [];
        $query->bindings['where'] = [];
        $query->where('posts.id', '=', $foreignType->getKey());
    };
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    core05CallMutationSurface(
        $surface,
        $queryHash,
        new Core05MutationResource,
        $foreignType->getKey(),
    )->assertStatus(422);

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($foreignType->fresh()->content)->toBe('unchanged')
        ->and($foreignType->fresh()->status)->toBe('draft');
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'bulk record select all' => 'bulk record select all',
    'bulk collection select all' => 'bulk collection select all',
    'Kanban update' => 'Kanban update',
]);

test('raw before-query predicates fail closed on every mutation surface', function (
    string $surface,
    string $rawVariant,
) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $foreignType = Core05NoKanbanFieldResource::create([
        'title' => 'Foreign raw callback target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::withoutGlobalScopes()->whereKey($foreignType->getKey())
    );

    Core05MutationResource::$beforeQueryCallback = static function ($query) use (
        $foreignType,
        $rawVariant,
    ): void {
        match ($rawVariant) {
            'raw OR' => $query->whereRaw('1 = 1 OR posts.id = ?', [$foreignType->getKey()]),
            'raw comment' => $query->whereRaw('1 = 1) OR posts.id = ? --', [$foreignType->getKey()]),
            'raw subquery' => $query->whereRaw(
                'EXISTS (SELECT 1) OR posts.id = ?',
                [$foreignType->getKey()],
            ),
            'nested raw' => $query->where(static function ($nested) use ($foreignType): void {
                $nested->whereRaw('1 = 1 OR posts.id = ?', [$foreignType->getKey()]);
            }),
        };
    };

    core05CallMutationSurface(
        $surface,
        $queryHash,
        new Core05MutationResource,
        $foreignType->getKey(),
    )->assertStatus(422);

    Core05MutationResource::$beforeQueryCallback = null;

    expect(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($foreignType->fresh()->content)->toBe('unchanged')
        ->and($foreignType->fresh()->status)->toBe('draft');
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'bulk record select all' => 'bulk record select all',
    'bulk collection select all' => 'bulk collection select all',
    'Kanban update' => 'Kanban update',
])->with([
    'raw OR' => 'raw OR',
    'raw comment' => 'raw comment',
    'raw subquery' => 'raw subquery',
    'nested raw' => 'nested raw',
]);

test('structured callback OR predicates stay grouped under trusted scope predicates', function (string $surface) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $foreignType = Core05NoKanbanFieldResource::create([
        'title' => 'Foreign structured callback target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::withoutGlobalScopes()->whereKey($foreignType->getKey())
    );

    Core05MutationResource::$beforeQueryCallback = static function ($query) use ($foreignType): void {
        $query
            ->where('posts.status', 'draft')
            ->orWhere('posts.id', $foreignType->getKey());
    };

    $result = core05CallMutationSurface(
        $surface,
        $queryHash,
        new Core05MutationResource,
        $foreignType->getKey(),
    );

    if (str_contains($surface, 'bulk')) {
        $result->assertHasErrors(['selected']);
    } else {
        $result->assertNotFound();
    }

    Core05MutationResource::$beforeQueryCallback = null;

    expect(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($foreignType->fresh()->content)->toBe('unchanged')
        ->and($foreignType->fresh()->status)->toBe('draft');
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'bulk record select all' => 'bulk record select all',
    'bulk collection select all' => 'bulk collection select all',
    'Kanban update' => 'Kanban update',
]);

test('before-query callback bindings must match their structured predicates', function (string $surface) {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Callback binding parity target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    Core05MutationResource::$beforeQueryCallback = static function (QueryBuilder $query): void {
        $query->where('posts.status', 'draft');
        $query->addBinding('detached-binding', 'where');
    };

    core05CallMutationSurface(
        $surface,
        null,
        new Core05MutationResource,
        $resource->getKey(),
    )->assertStatus(422);

    Core05MutationResource::$beforeQueryCallback = null;

    expect(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($resource->fresh()->content)->toBe('unchanged')
        ->and($resource->fresh()->status)->toBe('draft');
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'bulk record select all' => 'bulk record select all',
    'bulk collection select all' => 'bulk collection select all',
    'Kanban update' => 'Kanban update',
]);

test('before-query callbacks execute once from every mutation query source', function (
    string $surface,
    string $callbackSource,
) {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $mounted = $callbackSource === 'eager relation'
        ? new Core05EagerMutationResource
        : new Core05MutationResource;
    $resource = $mounted->newQuery()->create([
        'title' => 'Callback invocation target',
        'content' => 'unchanged',
        'status' => 'draft',
        'user_id' => $actor->getKey(),
    ]);
    $queryHash = null;

    match ($callbackSource) {
        'model global' => Core05MutationResource::$beforeQueryCallback = static function (
            QueryBuilder $query,
        ): void {},
        'index query' => Core05MutationResource::$countIndexBeforeQueryInvocations = true,
        'dynamic query' => $queryHash = DynamicFunctions::add(static function (): Builder {
            $query = Core05MutationResource::query();

            $query->getQuery()->beforeQuery(static function (QueryBuilder $query): void {
                $isMutationKeyQuery = collect((array) $query->columns)->contains(
                    fn (mixed $column): bool => is_string($column)
                        && str_contains($column, '__aura_mutation_key'),
                );

                if ($isMutationKeyQuery) {
                    Core05MutationResource::$dynamicBeforeQueryInvocations++;
                }
            });

            return $query;
        }),
        'eager relation' => Core05EagerMutationResource::$relationExpectedTransactionLevel = DB::connection()
            ->transactionLevel() + 1,
    };

    core05CallMutationSurface(
        $surface,
        $queryHash,
        $mounted,
        $resource->getKey(),
    )->assertHasNoErrors();

    $beforeQueryInvocations = match ($callbackSource) {
        'model global' => Core05MutationResource::$beforeQueryInvocations,
        'index query' => Core05MutationResource::$indexBeforeQueryInvocations,
        'dynamic query' => Core05MutationResource::$dynamicBeforeQueryInvocations,
        'eager relation' => Core05EagerMutationResource::$relationBeforeQueryInvocations,
    };

    Core05MutationResource::$beforeQueryCallback = null;
    Core05MutationResource::$countIndexBeforeQueryInvocations = false;
    Core05EagerMutationResource::$relationExpectedTransactionLevel = null;

    expect($beforeQueryInvocations)->toBe(1);
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'bulk record select all' => 'bulk record select all',
    'bulk collection select all' => 'bulk collection select all',
    'Kanban update' => 'Kanban update',
])->with([
    'model global' => 'model global',
    'index query' => 'index query',
    'dynamic query' => 'dynamic query',
    'eager relation' => 'eager relation',
]);

test('effective-scope callbacks cannot erase dynamic membership predicates', function () {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Dynamic callback target',
        'content' => 'outside-callback-scope',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(static function () use ($resource): Builder {
        $query = Core05MutationResource::query()
            ->where('posts.content', 'inside-callback-scope');

        $query->getQuery()->beforeQuery(static function ($query) use ($resource): void {
            $isMutationKeyQuery = collect((array) $query->columns)->contains(
                fn (mixed $column): bool => is_string($column)
                    && str_contains($column, '__aura_mutation_key'),
            );

            if (! $isMutationKeyQuery) {
                return;
            }

            $query->wheres = [];
            $query->bindings['where'] = [];
            $query->where('posts.id', '=', $resource->getKey());
        });

        return $query;
    });
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    core05CallMutationSurface(
        'single action',
        $queryHash,
        new Core05MutationResource,
        $resource->getKey(),
    )->assertStatus(422);

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($resource->fresh()->content)->toBe('outside-callback-scope');
});

test('before-query callbacks cannot detach mandatory where bindings', function () {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Binding integrity target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    Core05MutationResource::$authoritativeReadCallback = static function ($query): void {
        $query->bindings['where'] = [];
    };
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    core05CallMutationSurface(
        'single action',
        null,
        new Core05MutationResource,
        $resource->getKey(),
    )->assertStatus(422);

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($resource->fresh()->content)->toBe('unchanged');
});

test('single action rejects a row that concurrently leaves its index scope before locking', function () {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Index scope race target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    Core05MutationResource::$authoritativeReadCallback = static function ($query) use ($resource): void {
        $query->getConnection()->table('posts')
            ->where('id', $resource->getKey())
            ->update(['title' => 'Excluded by indexQuery']);
    };
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    core05CallMutationSurface(
        'single action',
        null,
        new Core05MutationResource,
        $resource->getKey(),
    )->assertNotFound();

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and($resource->fresh()->title)->toBe('Index scope race target')
        ->and($resource->fresh()->content)->toBe('unchanged');
});

test('Kanban rejects a row that concurrently leaves its Kanban scope before locking', function () {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Kanban scope race target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    Core05MutationResource::$authoritativeReadCallback = static function ($query) use ($resource): void {
        $query->getConnection()->table('posts')
            ->where('id', $resource->getKey())
            ->update(['title' => 'Excluded by kanbanQuery']);
    };
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    core05CallMutationSurface(
        'Kanban update',
        null,
        new Core05MutationResource,
        $resource->getKey(),
    )->assertNotFound();

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and($resource->fresh()->title)->toBe('Kanban scope race target')
        ->and($resource->fresh()->status)->toBe('draft');
});

test('explicit bulk rejects a row that concurrently leaves its dynamic scope before locking', function (
    string $surface,
) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Dynamic scope race target',
        'content' => 'eligible-for-dynamic-mutation',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()
            ->where('posts.content', 'eligible-for-dynamic-mutation')
    );

    Core05MutationResource::$authoritativeReadCallback = static function ($query) use ($resource): void {
        $query->getConnection()->table('posts')
            ->where('id', $resource->getKey())
            ->update(['content' => 'left-dynamic-mutation-scope']);
    };
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    core05CallMutationSurface(
        $surface,
        $queryHash,
        new Core05MutationResource,
        $resource->getKey(),
    )->assertHasErrors(['selected']);

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and($resource->fresh()->content)->toBe('eligible-for-dynamic-mutation');
})->with([
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
]);

test('select-all rejects a row that concurrently leaves its parent scope before locking', function (
    string $surface,
) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $parent = Core05MutationParentResource::create(['title' => 'Mutation parent']);
    $otherParent = Core05MutationParentResource::create(['title' => 'Other mutation parent']);
    $resource = Core05MutationResource::create([
        'title' => 'Parent scope race target',
        'content' => 'unchanged',
        'status' => 'draft',
        'parent_id' => $parent->getKey(),
    ]);

    Core05MutationResource::$authoritativeReadCallback = static function ($query) use ($otherParent, $resource): void {
        $query->getConnection()->table('posts')
            ->where('id', $resource->getKey())
            ->update(['parent_id' => $otherParent->getKey()]);
    };
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    $component = livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
        'parent' => $parent,
        'field' => $parent->fieldBySlug('children'),
    ])->set('selectAll', true);

    $result = match ($surface) {
        'bulk record select all' => $component->call('bulkAction', 'markBulkReviewed'),
        'bulk collection select all' => $component->call('bulkCollectionAction', 'captureCollectionAttributes'),
    };

    $result->assertHasErrors(['selected']);

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and($resource->fresh()->parent_id)->toBe($parent->getKey())
        ->and($resource->fresh()->content)->toBe('unchanged');
})->with([
    'bulk record select all' => 'bulk record select all',
    'bulk collection select all' => 'bulk collection select all',
]);

test('mutation records preserve the trusted mounted instance morph identity', function (string $surface) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MorphMutationResource::class, Core05MorphMutationPolicy::class);

    $resource = Core05MorphMutationResource::create([
        'title' => 'Instance morph mutation target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $mounted = (new Core05MorphMutationResource)->useMutationMorphClass('core05-instance-morph');
    $mutations = app(TableMutationDispatcher::class);
    $descriptor = new TableMutationModelDescriptor($mounted);
    $scope = $mounted->newQuery()->whereKey($resource->getKey());

    match ($surface) {
        'single action' => $mutations->dispatchAction(
            $scope,
            $descriptor,
            $resource->getKey(),
            'markReviewed',
            $mounted->getActions(),
        ),
        'bulk record' => $mutations->dispatchBulk(
            $scope,
            $descriptor,
            'markBulkReviewed',
            $mounted->getBulkActions(),
            [$resource->getKey()],
            false,
            'record',
        ),
        'bulk collection' => $mutations->dispatchBulk(
            $scope,
            $descriptor,
            'captureCollectionAttributes',
            $mounted->getBulkActions(),
            [$resource->getKey()],
            false,
            'collection',
        ),
        'Kanban update' => $mutations->dispatchFieldUpdate(
            $scope,
            $descriptor,
            $resource->getKey(),
            'status',
            'reviewed',
        ),
    };

    expect(Core05MorphMutationPolicy::$attempts)->toBeGreaterThanOrEqual(1)
        ->and(Core05MorphMutationPolicy::$morphClasses)->each->toBe('core05-instance-morph');

    $freshResource = $resource->fresh();

    match ($surface) {
        'single action' => expect($freshResource->content)->toBe('reviewed-by-action'),
        'bulk record' => expect($freshResource->content)->toBe('reviewed-by-bulk-action'),
        'bulk collection' => expect(
            json_decode($freshResource->content, true, flags: JSON_THROW_ON_ERROR)['ids']
        )->toBe([$resource->getKey()]),
        'Kanban update' => expect($freshResource->status)->toBe('reviewed'),
    };
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
]);

test('mutation selection authorization and handlers share one locked transaction', function (string $surface) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05TransactionMutationPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Transactional mutation target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    Core05MutationResource::$authoritativeQueryCallback = 'observe transaction';
    $expectedTransactionLevel = DB::connection()->transactionLevel() + 1;

    $mutationQueries = core05CaptureLockedMutationQueries(
        fn (): mixed => core05CallMutationSurface(
            $surface,
            null,
            new Core05MutationResource,
            $resource->getKey(),
        )->assertHasNoErrors(),
    );

    expect($mutationQueries)->not->toBeEmpty()
        ->and(collect($mutationQueries)->pluck('query'))->each->toContain('/* core05-lock-for-update */')
        ->and(Core05MutationResource::$authoritativeReadTransactionLevels)->not->toBeEmpty()
        ->and(Core05MutationResource::$authoritativeReadTransactionLevels)->each->toBe($expectedTransactionLevel)
        ->and(Core05TransactionMutationPolicy::$transactionLevels)->not->toBeEmpty()
        ->and(Core05TransactionMutationPolicy::$transactionLevels)->toContain($expectedTransactionLevel)
        ->and(Core05MutationResource::$updateTransactionLevels)->not->toBeEmpty()
        ->and(Core05MutationResource::$updateTransactionLevels)->each->toBe($expectedTransactionLevel);
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
]);

test('locking statements target only validated base rows before effective scope revalidation', function (string $surface) {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'MVCC scope target',
        'content' => 'eligible-for-mutation',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()
            ->where('posts.content', 'eligible-for-mutation')
    );

    $mutationQueries = core05CaptureLockedMutationQueries(
        fn (): mixed => core05CallMutationSurface(
            $surface,
            $queryHash,
            new Core05MutationResource,
            $resource->getKey(),
        )->assertHasNoErrors(),
    );
    $lockingSql = $mutationQueries[0]['query'] ?? null;

    expect($mutationQueries)->toHaveCount(1)
        ->and($lockingSql)->toBeString()
        ->toContain('select "posts".* from "posts"')
        ->toContain('where "posts"."id" in (?)')
        ->toContain('order by "posts"."id" asc')
        ->toContain('/* core05-lock-for-update */')
        ->not->toContain(' in (select ')
        ->not->toContain('distinct')
        ->not->toContain('group by')
        ->not->toContain('having');
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'bulk record select all' => 'bulk record select all',
    'bulk collection select all' => 'bulk collection select all',
    'Kanban update' => 'Kanban update',
]);

test('distinct and grouped effective scopes revalidate membership around a base-row lock', function (string $shape) {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Complex lock shape target',
        'content' => 'eligible-for-complex-lock',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(static function () use ($shape): Builder {
        $query = Core05MutationResource::query()
            ->where('posts.content', 'eligible-for-complex-lock');

        if ($shape === 'distinct') {
            return $query->distinct();
        }

        return $query
            ->groupBy('posts.id')
            ->havingRaw('count(*) >= ?', [1]);
    });

    $mutationQueries = core05CaptureLockedMutationQueries(
        fn (): mixed => core05CallMutationSurface(
            'single action',
            $queryHash,
            new Core05MutationResource,
            $resource->getKey(),
        )->assertHasNoErrors(),
    );
    $lockingSql = $mutationQueries[0]['query'] ?? null;

    expect($resource->fresh()->content)->toBe('reviewed-by-action')
        ->and($mutationQueries)->toHaveCount(1)
        ->and($lockingSql)->toBeString()
        ->toContain('select "posts".* from "posts"')
        ->toContain('where "posts"."id" in')
        ->toContain('/* core05-lock-for-update */')
        ->not->toContain('distinct')
        ->not->toContain('group by')
        ->not->toContain('having');
})->with([
    'distinct' => 'distinct',
    'group by and having' => 'group',
]);

test('aggregate effective scopes fail closed before authorization or handlers', function () {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Aggregate lock shape target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(static function (): Builder {
        $query = Core05MutationResource::query();
        $query->getQuery()->aggregate = ['function' => 'count', 'columns' => ['*']];

        return $query;
    });
    $mutationTransactionLevel = DB::connection()->transactionLevel() + 1;

    core05CallMutationSurface(
        'single action',
        $queryHash,
        new Core05MutationResource,
        $resource->getKey(),
    )->assertStatus(422);

    expect(Core05MutationBoundaryPolicy::$transactionLevels)->not->toContain($mutationTransactionLevel)
        ->and(Core05MutationResource::$updateInvocations)->toBe(0)
        ->and($resource->fresh()->content)->toBe('unchanged');
});

test('bulk mutation preserves display order while locking base rows in deterministic primary-key order', function (bool $selectAll) {
    $this->actingAs(createSuperAdmin());

    $resources = collect([
        Core05MutationResource::create([
            'title' => 'First ordered lock target',
            'content' => 'unchanged',
            'status' => 'draft',
        ]),
        Core05MutationResource::create([
            'title' => 'Second ordered lock target',
            'content' => 'unchanged',
            'status' => 'draft',
        ]),
        Core05MutationResource::create([
            'title' => 'Third ordered lock target',
            'content' => 'unchanged',
            'status' => 'draft',
        ]),
    ]);
    $expectedIds = $resources->pluck('id')->sortDesc()->values()->all();
    Core05MutationResource::$authoritativeQueryCallback = 'observe transaction';

    $component = livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
    ])->set('selected', $resources->pluck('id')->reverse()->values()->all());

    if ($selectAll) {
        $component->set('selectAll', true);
    }

    $mutationQueries = core05CaptureLockedMutationQueries(
        fn (): mixed => $component->call('bulkCollectionAction', 'captureCollectionAttributes')
            ->assertHasNoErrors(),
    );

    $receiver = Core05MutationResource::findOrFail($expectedIds[0]);
    $snapshot = json_decode($receiver->content, true, flags: JSON_THROW_ON_ERROR);

    expect($snapshot['ids'])->toBe($expectedIds)
        ->and($mutationQueries)->not->toBeEmpty()
        ->and(collect($mutationQueries)->pluck('query'))->each->toContain('order by "posts"."id" asc')
        ->and(collect($mutationQueries)->pluck('query'))->each->toContain('/* core05-lock-for-update */');
})->with([
    'explicit selection' => false,
    'select all' => true,
]);

test('mutation transactions retry a deadlock before lock acquisition', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $mounted = (new Core05MutationResource)->setConnection('core05_mutation_secondary');
    $resource = core05CreateMutationResourceOnSecondaryConnection([
        'title' => 'Deadlock retry target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    core05AuthenticateActorOnSecondaryConnection($actor);

    Core05MutationResource::$beforeQueryCallback = static function ($query): void {
        if (Core05MutationResource::$beforeQueryInvocations === 1) {
            throw new PDOException('database is locked');
        }
    };

    app(TableMutationDispatcher::class)->dispatchAction(
        $mounted->newQuery(),
        new TableMutationModelDescriptor($mounted),
        $resource->getKey(),
        'markReviewed',
        $mounted->getActions(),
    );

    Core05MutationResource::$beforeQueryCallback = null;

    expect(Core05MutationResource::$beforeQueryInvocations)->toBe(2)
        ->and(Core05MutationBoundaryPolicy::$attempts)->toBe(1)
        ->and(Core05MutationResource::$updateInvocations)->toBe(1)
        ->and($resource->fresh()->content)->toBe('reviewed-by-action');
});

test('mutation transactions never retry after a handler effect begins', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    Gate::policy(Core05MutationResource::class, Core05MutationBoundaryPolicy::class);

    $mounted = (new Core05MutationResource)->setConnection('core05_mutation_secondary');
    $resource = core05CreateMutationResourceOnSecondaryConnection([
        'title' => 'Post-handler deadlock target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    core05AuthenticateActorOnSecondaryConnection($actor);

    expect(fn () => app(TableMutationDispatcher::class)->dispatchAction(
        $mounted->newQuery(),
        new TableMutationModelDescriptor($mounted),
        $resource->getKey(),
        'deadlockAfterExternalEffect',
        $mounted->getActions(),
    ))->toThrow(PDOException::class, 'database is locked');

    expect(Core05MutationResource::$externalEffects)->toBe(1)
        ->and(Core05MutationResource::$updateInvocations)->toBe(1)
        ->and($resource->fresh()->content)->toBe('unchanged');
});

test('a denied mutation rolls back row changes made during authorization', function (string $surface) {
    $this->actingAs(createSuperAdmin());
    Gate::policy(Core05MutationResource::class, Core05DenyingMutationPolicy::class);

    $resource = Core05MutationResource::create([
        'title' => 'Authorization rollback target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $mounted = new Core05MutationResource;
    $mutations = app(TableMutationDispatcher::class);
    $descriptor = new TableMutationModelDescriptor($mounted);
    $scope = $mounted->newQuery()->whereKey($resource->getKey());

    expect(fn () => match ($surface) {
        'single action' => $mutations->dispatchAction(
            $scope,
            $descriptor,
            $resource->getKey(),
            'markReviewed',
            $mounted->getActions(),
        ),
        'bulk record' => $mutations->dispatchBulk(
            $scope,
            $descriptor,
            'markBulkReviewed',
            $mounted->getBulkActions(),
            [$resource->getKey()],
            false,
            'record',
        ),
        'bulk collection' => $mutations->dispatchBulk(
            $scope,
            $descriptor,
            'captureCollectionAttributes',
            $mounted->getBulkActions(),
            [$resource->getKey()],
            false,
            'collection',
        ),
        'Kanban update' => $mutations->dispatchFieldUpdate(
            $scope,
            $descriptor,
            $resource->getKey(),
            'status',
            'reviewed',
        ),
    })->toThrow(AuthorizationException::class);

    expect($resource->fresh()->content)->toBe('unchanged')
        ->and($resource->fresh()->status)->toBe('draft')
        ->and(Core05MutationResource::$updateInvocations)->toBe(0);
})->with([
    'single action' => 'single action',
    'bulk record' => 'bulk record',
    'bulk collection' => 'bulk collection',
    'Kanban update' => 'Kanban update',
]);

test('chunked bulk selection bounds every lock query and rolls back a denial in a later chunk', function () {
    $this->actingAs(createSuperAdmin());
    config()->set('aura.security.table_mutations.max_records', 5);
    config()->set('aura.security.table_mutations.chunk_size', 2);
    Gate::policy(Core05MutationResource::class, Core05DenyLastChunkMutationPolicy::class);
    $resources = collect(range(1, 5))->map(fn (int $number) => Core05MutationResource::create([
        'title' => 'Chunked authorization target '.$number,
        'content' => 'unchanged',
        'status' => 'draft',
    ]));
    Core05DenyLastChunkMutationPolicy::$deniedKey = $resources->last()->getKey();
    $mounted = new Core05MutationResource;

    $lockingQueries = core05CaptureLockedMutationQueries(
        fn (): mixed => expect(fn (): mixed => app(TableMutationDispatcher::class)->dispatchBulk(
            $mounted->newQuery(),
            new TableMutationModelDescriptor($mounted),
            'markBulkReviewed',
            $mounted->getBulkActions(),
            [],
            true,
            'record',
        ))->toThrow(AuthorizationException::class),
    );

    expect($lockingQueries)->toHaveCount(3)
        ->and(collect($lockingQueries)->every(
            fn (array $query): bool => count($query['bindings']) <= 2,
        ))->toBeTrue()
        ->and($resources->map->fresh()->pluck('content')->all())->each->toBe('unchanged');
});

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

test('select all applies its cap after duplicate join identities are deduplicated', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);
    config()->set('aura.security.table_mutations.max_records', 2);
    config()->set('aura.security.table_mutations.chunk_size', 1);
    $duplicated = Core05MutationResource::create([
        'title' => 'Duplicated first identity',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $second = Core05MutationResource::create([
        'title' => 'Second identity',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    core05CreateMutationCollision($duplicated, $actor, duplicates: 5);
    core05CreateMutationCollision($second, $actor);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('selectAllRows')
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasNoErrors();

    expect($duplicated->fresh()->content)->toBe('reviewed-by-bulk-action')
        ->and($second->fresh()->content)->toBe('reviewed-by-bulk-action');
});

test('explicit selections deduplicate canonical identities before applying the cap', function () {
    $this->actingAs(createSuperAdmin());
    config()->set('aura.security.table_mutations.max_records', 2);
    config()->set('aura.security.table_mutations.chunk_size', 1);
    $first = Core05MutationResource::create([
        'title' => 'First explicit identity',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $second = Core05MutationResource::create([
        'title' => 'Second explicit identity',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('selected', [
            $second->getKey(),
            (string) $second->getKey(),
            $first->getKey(),
            (string) $first->getKey(),
        ])
        ->call('bulkCollectionAction', 'captureCollectionAttributes')
        ->assertHasNoErrors();

    expect(Core05MutationResource::$capturedCollectionIdChunks)->toBe([
        [$second->getKey()],
        [$first->getKey()],
    ]);
});

test('select all preserves the effective displayed order before applying a query limit', function () {
    $this->actingAs(createSuperAdmin());
    $oldest = Core05MutationResource::create([
        'title' => 'Oldest',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $middle = Core05MutationResource::create([
        'title' => 'Middle',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $newest = Core05MutationResource::create([
        'title' => 'Newest',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()->limit(2)
    );

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('selectAllRows')
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasNoErrors();

    expect($oldest->fresh()->content)->toBe('unchanged')
        ->and($middle->fresh()->content)->toBe('reviewed-by-bulk-action')
        ->and($newest->fresh()->content)->toBe('reviewed-by-bulk-action');
});

test('select all exclusions do not backfill rows outside the displayed query limit', function () {
    $this->actingAs(createSuperAdmin());
    $oldest = Core05MutationResource::create([
        'title' => 'Oldest excluded-window resource',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $middle = Core05MutationResource::create([
        'title' => 'Middle excluded-window resource',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $newest = Core05MutationResource::create([
        'title' => 'Newest excluded-window resource',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()->limit(2)
    );

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('selectAllRows')
        ->call('updateRowSelection', [$newest->getKey()], false)
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasNoErrors();

    expect($oldest->fresh()->content)->toBe('unchanged')
        ->and($middle->fresh()->content)->toBe('reviewed-by-bulk-action')
        ->and($newest->fresh()->content)->toBe('unchanged');
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

    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $foreignTeam = foreignTeam();
    $foreignAttributes = [
        'title' => 'Foreign unscoped dynamic row',
        'content' => 'unchanged',
        'status' => 'draft',
        'user_id' => $foreignTeam->user_id,
    ];

    expect(fn () => Core05MutationResource::withoutGlobalScopes()->create([
        ...$foreignAttributes,
        'team_id' => $foreignTeam->getKey(),
    ]))->toThrow(LogicException::class, 'Use createForTeamForSystem()');

    $foreignResource = Core05MutationResource::createForTeamForSystem(
        $foreignTeam->getKey(),
        $foreignAttributes,
        $actor->getConnection(),
    );
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

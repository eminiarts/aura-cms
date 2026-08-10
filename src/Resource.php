<?php

namespace Aura\Base;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\TypeScope;
use Aura\Base\Resources\User;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InitialPostFields;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Aura\Base\Traits\SaveFieldAttributes;
use Aura\Base\Traits\SaveMetaFields;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Events\NullDispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PDO;

/**
 * Dynamic property access on a Resource resolves in a fixed precedence order,
 * centralized in resolveDynamicAttribute() as the single source of truth (the
 * __get magic method is a thin delegate to it):
 *
 *   1. Real Eloquent state via parent::__get — a declared attribute, an
 *      accessor, or a loaded/lazy relation.
 *   2. Any non-null result from (1) wins as-is, including falsy 0/''/false.
 *   3. Otherwise, if $key is a relation field slug, the field's getRelation()
 *      result (with any falsy value coerced to an empty collection).
 *   4. Otherwise, the computed value from the `fields` accessor, if present.
 *   5. Otherwise null.
 *
 * See resolveDynamicAttribute() for the annotated control flow.
 *
 * @property-read Collection $fields  Computed input-field map (getFieldsAttribute()).
 * @property-read mixed $meta  The meta relation / normalized meta map (see getMeta()).
 */
class Resource extends Model implements DefinesFields
{
    use AuraModelConfig;
    use HasFactory;
    use HasTimestamps;

    // Aura
    use InitialPostFields;
    use InputFields;
    use InteractsWithTable;
    use SaveFieldAttributes;
    use SaveMetaFields;

    public $fieldsAttributeCache;

    protected $appends = ['fields'];

    /**
     * Re-entrancy guard for getFieldsAttribute(): true while the fields
     * collection is mid-build. Building resolves each field value, which can
     * read back into this accessor (e.g. relation loading dereferences the
     * model's key via __get) before the cache is populated. The guard lets that
     * nested read see an empty collection instead of triggering an unbounded
     * rebuild.
     */
    protected bool $buildingFieldsAttribute = false;

    protected $fillable = ['title', 'content', 'type', 'status', 'fields', 'slug', 'user_id', 'parent_id', 'order', 'team_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['meta'];

    /**
     * Per-instance cache of the normalized meta map (see getMeta()).
     *
     * @var Collection|null
     */
    protected $normalizedMetaCache;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * Per-instance cache of preloaded table-display values, keyed by field slug.
     *
     * Primed by PreloadsTableDisplay implementations after pagination so that
     * a field's display() can resolve without a per-row query. array_key_exists
     * distinguishes "primed but resolved to null" from "not primed".
     *
     * @var array<string, mixed>
     */
    protected array $tableDisplayCache = [];

    protected $with = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->baseFillable = $this->getFillable();

        // Merge fillable fields from fields
        $this->mergeFillable($this->inputFieldsSlugs());

        if ($this->usesMeta()) {
            $this->with[] = 'meta';
        }

        // The 'fields' accessor is expensive to serialize (it resolves every
        // input field value). Appending it to every array/JSON serialization is
        // opt-in behind a config flag. Default (true) keeps the legacy behavior;
        // when disabled, callers can still opt in per-model via ->append('fields').
        if (! config('aura.features.legacy_fields_append', true)) {
            $this->appends = array_values(array_diff($this->appends, ['fields']));
        }
    }

    public function __call($method, $parameters)
    {
        if ($this->getFieldSlugs()->contains($method)) {

            $fieldClass = $this->fieldClassBySlug($method);

            if ($fieldClass->isRelation()) {

                $field = $this->fieldBySlug($method);

                return $fieldClass->relationship($this, $field);
            }
        }

        // Default behavior for methods not handled dynamically
        return parent::__call($method, $parameters);
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->resolveDynamicAttribute($key);
    }

    /**
     * Mirror __get's resolution order for isset()/empty()/null-coalescing.
     *
     * Eloquent's native Model::__isset only inspects real attributes and
     * relations, so it reports meta-backed and computed field slugs as "unset"
     * — which silently breaks `$model->metaField ?? 'default'`, `pluck()` and
     * `empty()` for exactly those dynamic attributes. Since __get resolves a key
     * through resolveDynamicAttribute() (Eloquent state → relation field →
     * computed `fields` value), isset() must report true whenever that same
     * ladder would yield a non-null value; resolving once here keeps __isset and
     * __get in lock-step, including the empty-collection coercion for relation
     * slugs.
     *
     * Recursion note: resolving a meta/computed slug builds the `fields`
     * accessor, whose construction (resolveFieldValue) probes for a *real*
     * Eloquent attribute. Those probes deliberately use parent::__isset() —
     * native, meta-blind semantics — so this meta-aware resolution can never
     * recurse into itself, and the table-display fast path is never forced into
     * a full fields build just to test attribute presence.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->resolveDynamicAttribute($key));
    }

    /**
     * Assign a deliberate owner from trusted infrastructure.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function assignOwnerForSystem(int|string $ownerId, array $attributes = []): bool
    {
        $attributes['user_id'] = $ownerId;
        $this->fill($attributes);

        return $this->saveSystemResource(
            trustedOwnerIntent: true,
            trustedOwnerId: $ownerId,
        );
    }

    /**
     * @return HasMany
     */
    public function attachment()
    {
        return $this->hasMany(self::class, 'post_parent')
            ->where('post_type', 'attachment');
    }

    /**
     * @return HasMany
     */
    public function children()
    {
        return $this->hasMany(get_class($this), 'parent_id');
    }

    public function clearFieldsAttributeCache()
    {
        $this->fieldsAttributeCache = null;
        $this->normalizedMetaCache = null;

        if ($this->usesMeta()) {
            $this->load('meta'); // This will refresh only the 'meta' relationship
        }

    }

    /**
     * Create a row for a deliberate owner from trusted infrastructure.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createForOwnerForSystem(
        int|string $ownerId,
        array $attributes = [],
        ?Connection $connection = null,
    ): static {
        $attributes['user_id'] = $ownerId;
        $resource = static::resourceModelOnConnection($connection);
        $instance = static::ensureStaticResource($resource->newInstance($attributes));
        $instance->saveSystemResource(trustedOwnerIntent: true, trustedOwnerId: $ownerId);

        return $instance;
    }

    /**
     * Create a team-owned row from trusted infrastructure.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createForTeamForSystem(
        int|string $teamId,
        array $attributes = [],
        ?Connection $connection = null,
    ): static {
        static::ensureTeamWriteIsSupported();

        $attributes['team_id'] = $teamId;
        $resource = static::resourceModelOnConnection($connection);
        $instance = static::ensureStaticResource($resource->newInstance($attributes));
        $hasOwnerIntent = array_key_exists('user_id', $attributes);
        $instance->saveSystemResource(
            trustedOwnerIntent: $hasOwnerIntent,
            trustedOwnerId: $attributes['user_id'] ?? null,
            trustedTeamIntent: true,
            trustedTeamId: $teamId,
        );

        return $instance;
    }

    /**
     * Create a shared row visible to every team after policy authorization.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createGlobal(array $attributes = [], ?Connection $connection = null): static
    {
        $authenticatedUser = auth()->user();

        if ($connection === null && $authenticatedUser instanceof User) {
            $connection = $authenticatedUser->getConnection();
        }

        $resource = static::resourceModelOnConnection($connection);

        Gate::authorize('createGlobal', $resource);

        if (! $authenticatedUser instanceof User) {
            throw new \LogicException('Only authenticated Aura users may authorize global resources.');
        }

        return static::createGlobalRecord(
            $attributes,
            $resource->getConnection(),
            $authenticatedUser,
        );
    }

    /**
     * Create a shared row from trusted infrastructure such as an installer,
     * seeder, or background catalog synchronization job.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createGlobalForSystem(
        array $attributes = [],
        ?Connection $connection = null,
    ): static {
        static::ensureGlobalWriteIsSupported();
        $resource = static::resourceModelOnConnection($connection);

        return static::createGlobalRecord(
            $attributes,
            $resource->getConnection(),
            trustedOwnerIntent: array_key_exists('user_id', $attributes),
        );
    }

    /**
     * Resolve or create one shared global row from trusted infrastructure.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    public static function firstOrCreateGlobalForSystem(
        array $attributes,
        array $values = [],
        ?Connection $connection = null,
    ): static {
        static::ensureGlobalWriteIsSupported();

        $resource = static::resourceModelOnConnection($connection);

        return static::firstOrCreateGlobalRecord($attributes, $values, $resource);
    }

    public function getBulkActions()
    {
        return $this->bulkActions;
    }

    public function getFieldsAttribute()
    {
        if (isset($this->fieldsAttributeCache) && $this->fieldsAttributeCache !== null) {
            return $this->fieldsAttributeCache;
        }

        // Re-entrancy guard: a nested read of this accessor while it is still
        // building (before the cache is populated) gets an empty collection
        // rather than kicking off an unbounded rebuild. See the property docblock.
        if ($this->buildingFieldsAttribute) {
            return collect();
        }

        $this->buildingFieldsAttribute = true;

        try {
            // Get fields only once and store in a variable
            $fieldsWithoutLogic = $this->getFieldsWithoutConditionalLogic();

            $this->fieldsAttributeCache = collect($fieldsWithoutLogic)
                ->filter(function ($value, $key) use ($fieldsWithoutLogic) {
                    // Early return if not base fillable and not hidden
                    if (! $this->isBaseFillable($key) && ! in_array($key, $this->hidden)) {
                        return true;
                    }

                    // Skip if key is hidden
                    if (in_array($key, $this->hidden)) {
                        return false;
                    }

                    // Check conditional logic only if we haven't already filtered out the field
                    $field = $this->fieldBySlug($key);

                    return ConditionalLogic::shouldDisplayField($this, $field, ['fields' => $fieldsWithoutLogic]);
                });
        } finally {
            $this->buildingFieldsAttribute = false;
        }

        return $this->fieldsAttributeCache;
    }

    public function getFieldsWithoutConditionalLogic()
    {
        $meta = $this->getMeta();

        $defaultValues = collect($this->inputFieldsSlugs())
            ->mapWithKeys(fn ($value) => [$value => null])
            ->filter(function ($value, $key) {
                return strpos($key, '.') === false;
            })
            ->map(function ($value, $key) use ($meta) {
                return $this->resolveFieldValue($key, $meta);
            });

        return $defaultValues->toArray();
    }

    public function getMeta($key = null)
    {
        if ($this->usesCustomTable() && ! $this->usesMeta()) {
            return collect();
        }

        if ($this->usesMeta() && optional($this)->meta && ! is_string($this->meta)) {

            // Build (and cache) the normalized meta map once per instance. The
            // pluck/cast/map scan is otherwise repeated for every displayed
            // cell. The cache is invalidated whenever the meta relation is
            // replaced (setRelation/unsetRelation) or fields are cleared.
            if ($this->normalizedMetaCache === null) {
                $meta = $this->meta->pluck('value', 'key');

                // Cast Attributes
                $meta = $meta->map(function ($meta, $key) {
                    $field = $this->fieldBySlug($key);

                    $class = $this->fieldClassBySlug($key);

                    if ($class && method_exists($class, 'get')) {
                        return $class->get($class, $meta, $field);
                    }

                    return $meta;
                });

                $this->normalizedMetaCache = $meta;
            }

            if ($key) {
                return $this->normalizedMetaCache[$key] ?? null;
            }

            return $this->normalizedMetaCache;
        }

        return collect();
    }

    public function getSearchableFields()
    {
        // get input fields and remove the ones that are not searchable
        $fields = $this->inputFields()->filter(function ($field) {
            // if $field is array or undefined, then we don't want to use it
            if (! is_array($field) || ! isset($field['searchable'])) {
                return false;
            }

            return $field['searchable'];
        });

        return $fields;
    }

    public function getTableDisplayValue(string $slug): mixed
    {
        return $this->tableDisplayCache[$slug] ?? null;
    }

    public function hasTableDisplayValue(string $slug): bool
    {
        return array_key_exists($slug, $this->tableDisplayCache);
    }

    public function isBaseFillable($key)
    {
        return in_array($key, $this->baseFillable);
    }

    /**
     * Global-write persistence authority is never observable by application
     * callbacks. Retained as a compatibility probe for existing integrations.
     */
    public static function isGlobalWriteInProgress(?Resource $resource = null): bool
    {
        return false;
    }

    // Override isRelation
    public function isRelation($key)
    {
        $modelMethods = get_class_methods($this);

        $possibleRelationMethods = [$key, Str::camel($key)];

        foreach ($possibleRelationMethods as $method) {

            if (in_array($method, $modelMethods) && ($this->{$method}() instanceof Relation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Move a shared global row into one team through an explicit, authorized
     * tenancy transition rather than accepting team_id from an ordinary form.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function moveGlobalToTeam(?int $teamId, array $attributes = []): bool
    {
        static::ensureGlobalWriteIsSupported();

        if (! $this->exists || $this->getAttribute('team_id') !== null) {
            throw new \LogicException('Only a persisted global resource can be moved to a team.');
        }

        if ($teamId === null) {
            throw new \LogicException('A team is required when moving a global resource.');
        }

        Gate::authorize('update', $this);

        return TeamScope::forTeam($teamId, function () use ($attributes, $teamId): bool {
            $this->fill($attributes);
            $this->setAttribute('team_id', $teamId);

            return $this->save();
        }, $this->getConnection());
    }

    /**
     * Move a row to a team from trusted infrastructure.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function moveToTeamForSystem(int|string $teamId, array $attributes = []): bool
    {
        static::ensureTeamWriteIsSupported();

        $this->fill($attributes);
        $this->setAttribute('team_id', $teamId);

        return $this->saveSystemResource(
            trustedOwnerIntent: array_key_exists('user_id', $attributes),
            trustedOwnerId: $attributes['user_id'] ?? null,
            trustedTeamIntent: true,
            trustedTeamId: $teamId,
        );
    }

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
    }

    /**
     * Promote an existing team row through the same global-write invariant and
     * policy used by createGlobal().
     *
     * @param  array<string, mixed>  $attributes
     */
    public function promoteToGlobal(array $attributes = []): bool
    {
        static::ensureGlobalWriteIsSupported();

        if (! $this->exists) {
            throw new \LogicException('Only a persisted resource can be promoted globally.');
        }

        Gate::authorize('update', $this);
        Gate::authorize('createGlobal', $this);

        $this->fill($attributes);
        $this->setAttribute('team_id', null);

        $authenticatedUser = auth()->user();

        if (! $authenticatedUser instanceof User) {
            throw new \LogicException('Only authenticated Aura users may authorize global resources.');
        }

        return $this->saveGlobalResource($authenticatedUser, authorizeUpdate: true);
    }

    /**
     * Resolve a single field's raw (pre-display) value.
     *
     * Extracted from getFieldsWithoutConditionalLogic() so that table display
     * can resolve just one requested field instead of building the entire
     * fields collection for every cell. The logic is intentionally identical to
     * the per-slug closure that previously lived inside the accessor.
     *
     * @param  Collection|null  $meta  The normalized meta map (defaults to getMeta()).
     * @return mixed
     */
    public function resolveFieldValue(string $slug, $meta = null)
    {
        $meta ??= $this->getMeta();

        $key = $slug;
        $value = null;

        $class = $this->fieldClassBySlug($key);
        $field = $this->fieldBySlug($key);

        if ($class && method_exists($class, 'isRelation') && $class->isRelation($field) && method_exists($class, 'get') && $field['type'] != 'Aura\\Base\\Fields\\Roles') {
            return $class->get($class, $this->{$key}, $field);
        }

        // Deliberately meta-BLIND probes: this method COMPUTES the value later
        // surfaced (meta-aware) via __get/__isset, so it must ask only whether a
        // *real* Eloquent attribute/relation exists for this slug. parent::__isset()
        // is the native check (identical to the historical isset($this->{$key})
        // before Resource declared its own __isset) — using the meta-aware isset()
        // here would recurse into the fields build and defeat the display fast path.
        if ($class && parent::__isset($key) && method_exists($class, 'get')) {
            return $class->get($class, $this->{$key}, $field);
        }

        if (parent::__isset($key)) {
            return $this->{$key};
        }

        if ($class && isset($this->attributes[$key]) && method_exists($class, 'get')) {
            return $class->get($class, $this->attributes[$key], $field);
        }

        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        $method = 'get'.Str::studly($key).'Field';

        if (method_exists($this, $method)) {
            return $this->{$method}($value);
        }

        if ($class && parent::__isset($key) && method_exists($class, 'get')) {
            return $class->get($class, $this->{$key} ?? null, $field);
        }

        if (optional($field)['polymorphic_relation'] === false && optional($field)['multiple'] === false) {
            return isset($meta[$key]) ? [$meta[$key]] : [];
        }

        return $meta[$key] ?? $value;
    }

    /**
     * @return HasMany
     */
    public function revision()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('post_type', 'revision');
    }

    public function setRelation($relation, $value)
    {
        if ($relation === 'meta') {
            $this->normalizedMetaCache = null;
            $this->fieldsAttributeCache = null;
        }

        return parent::setRelation($relation, $value);
    }

    public function setTableDisplayValue(string $slug, mixed $value): void
    {
        $this->tableDisplayCache[$slug] = $value;
    }

    public function unsetRelation($relation)
    {
        if ($relation === 'meta') {
            $this->normalizedMetaCache = null;
            $this->fieldsAttributeCache = null;
        }

        return parent::unsetRelation($relation);
    }

    /**
     * Resolve and update, or create, one shared global row from trusted
     * infrastructure.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    public static function updateOrCreateGlobalForSystem(
        array $attributes,
        array $values = [],
        ?Connection $connection = null,
    ): static {
        static::ensureGlobalWriteIsSupported();

        $resource = static::resourceModelOnConnection($connection);

        return static::updateOrCreateGlobalRecord($attributes, $values, $resource);
    }

    /**
     * Get the User associated with the Content
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(config('aura.resources.user'));
    }

    public function widgets()
    {
        if (! $this->getWidgets()) {
            return;
        }

        return collect($this->getWidgets())->map(function ($item) {
            // $item['widget'] = app($item['type'])->widget($item);

            return $item;
        });
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (! static::$customTable) {
            static::addGlobalScope(app(TypeScope::class));
        }

        static::addGlobalScope(app(TeamScope::class));

        static::addGlobalScope(app(ScopedScope::class));

        static::creating(function ($model) {});

        static::saved(function ($model) {
            $model->clearFieldsAttributeCache();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected static function createGlobalRecord(
        array $attributes,
        ?Connection $connection = null,
        ?User $authenticatedUser = null,
        bool $trustedOwnerIntent = false,
    ): static {
        static::ensureGlobalWriteIsSupported();

        $resource = static::resourceModelOnConnection($connection);

        return static::createGlobalResourceInstance(
            $attributes,
            $resource,
            $authenticatedUser,
            $trustedOwnerIntent,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected static function createGlobalResourceInstance(
        array $attributes,
        Resource $resource,
        ?User $authenticatedUser = null,
        bool $trustedOwnerIntent = false,
    ): static {
        $attributes['team_id'] = null;
        $instance = static::ensureStaticResource($resource->newInstance($attributes));

        return tap($instance, function (Resource $instance) use ($authenticatedUser, $trustedOwnerIntent): void {
            $instance->saveGlobalResource($authenticatedUser, trustedOwnerIntent: $trustedOwnerIntent);
        });
    }

    protected static function ensureGlobalWriteIsSupported(): void
    {
        if (! config('aura.teams') || ! static::sharesRecordsAcrossTeams()) {
            throw new \LogicException('Global writes require teams and an opted-in shared resource.');
        }
    }

    protected static function ensureStaticResource(Model $resource): static
    {
        if (! $resource instanceof static) {
            throw new \LogicException('The configured resource query returned an unexpected model type.');
        }

        return $resource;
    }

    protected static function ensureTeamWriteIsSupported(): void
    {
        if (! config('aura.teams')) {
            throw new \LogicException('Team writes require teams to be enabled.');
        }
    }

    /**
     * Keep connection authorization active even when Eloquent events are muted.
     *
     * @param  string  $event
     * @param  bool  $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if ($event !== 'deleting' && $event !== 'forceDeleting') {
            if ($event === 'saving' && ! static::getEventDispatcher() instanceof NullDispatcher) {
                $this->prepareFieldAttributesForPersistence();
            }

            return parent::fireModelEvent($event, $halt);
        }

        $connectionIdentity = User::connectionCacheIdentity($this->getConnection());

        $this->ensureDeleteUsesAuthenticatedConnection();

        $result = parent::fireModelEvent($event, $halt);

        if ($connectionIdentity !== User::connectionCacheIdentity($this->getConnection())) {
            throw new \LogicException('A resource connection cannot change during deletion.');
        }

        $this->ensureDeleteUsesAuthenticatedConnection();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    protected static function firstOrCreateGlobalRecord(
        array $attributes,
        array $values,
        Resource $resource,
    ): static {
        $attributes['team_id'] = null;
        unset($values['team_id']);

        $query = $resource->newQueryWithoutScopes()->useWritePdo();
        $existing = $resource->firstGlobalRecordOnCapturedWriter($query, $attributes);

        if ($existing !== null) {
            return static::ensureStaticResource($existing);
        }

        $create = fn (): static => static::createGlobalResourceInstance(
            array_merge($attributes, $values),
            $resource,
            trustedOwnerIntent: array_key_exists('user_id', array_merge($attributes, $values)),
        );

        try {
            return $resource->getConnection()->transactionLevel() > 0
                ? $resource->getConnection()->transaction($create)
                : $create();
        } catch (UniqueConstraintViolationException $exception) {
            $existing = $resource->firstGlobalRecordOnCapturedWriter($query, $attributes);

            return $existing !== null
                ? static::ensureStaticResource($existing)
                : throw $exception;
        }
    }

    protected static function isOwnerWriteAuthorized(
        int|string $ownerId,
        Connection $connection,
    ): bool {
        $actor = auth()->user();

        return $actor instanceof User
            && User::connectionCacheIdentity($actor->getConnection()) === User::connectionCacheIdentity($connection)
            && (string) $actor->getKey() === (string) $ownerId;
    }

    protected function performInsert(Builder $query)
    {
        $query->getQuery()->beforeQuery(fn (): bool => $this->authorizeOrdinaryPersistence());

        return parent::performInsert($query);
    }

    protected function performUpdate(Builder $query)
    {
        $query->getQuery()->beforeQuery(fn (): bool => $this->authorizeOrdinaryPersistence());

        return parent::performUpdate($query);
    }

    protected static function resourceModelOnConnection(?Connection $connection = null): static
    {
        /** @var static $configuredResource */
        $configuredResource = app(static::class);
        $resource = $configuredResource->newInstance();

        if ($connection) {
            $resource->setConnection($connection->getName());
        }

        return $resource;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    protected static function updateOrCreateGlobalRecord(
        array $attributes,
        array $values,
        Resource $resource,
    ): static {
        unset($values['team_id']);
        $instance = static::firstOrCreateGlobalRecord($attributes, $values, $resource);

        if (! $instance->wasRecentlyCreated) {
            $instance->fill($values);
            $merged = array_merge($attributes, $values);
            $instance->saveGlobalResource(
                trustedOwnerIntent: array_key_exists('user_id', $merged),
            );
        }

        return $instance;
    }

    private function assertPrivilegedConnectionState(
        Connection $connection,
        PDO $writePdo,
        int $transactionLevel,
    ): void {
        $writerChanged = $connection->getRawPdo() !== $writePdo;
        $transactionChanged = $connection->transactionLevel() !== $transactionLevel
            || ($transactionLevel > 0) !== $writePdo->inTransaction();

        if (! $writerChanged && ! $transactionChanged) {
            return;
        }

        $this->restorePhysicalWriter($connection, $writePdo, $transactionLevel);

        throw new \LogicException('A named write cannot change its physical database writer or transaction state.');
    }

    private function authorizeOrdinaryPersistence(): bool
    {
        static::authorizeInitialPostFieldPersistence($this);

        return true;
    }

    private function authorizePrivilegedPersistence(
        Connection $connection,
        Builder $query,
        Grammar $queryGrammar,
        Processor $queryProcessor,
        PDO $writePdo,
        string $table,
        int|string|null $teamId,
        mixed $ownerId,
        ?User $authenticatedUser,
        bool $authorizeUpdate,
        bool $globalWrite,
        string $keyName,
        mixed $keyForSaveQuery,
        bool $assertSaveKey,
        int $transactionLevel,
        ?array $expectedWheres = null,
        array $expectedWhereBindings = [],
    ): bool {
        if ($globalWrite) {
            static::ensureGlobalWriteIsSupported();
        }

        $assertIntent = function () use (
            $connection,
            $query,
            $queryGrammar,
            $queryProcessor,
            $writePdo,
            $table,
            $teamId,
            $ownerId,
            $keyName,
            $keyForSaveQuery,
            $assertSaveKey,
            $transactionLevel,
            $expectedWheres,
            $expectedWhereBindings,
        ): void {
            $this->assertPrivilegedConnectionState($connection, $writePdo, $transactionLevel);

            if ($this->getConnection() !== $connection
                || $this->getTable() !== $table
                || $this->getKeyName() !== $keyName
                || ($assertSaveKey && $this->getKeyForSaveQuery() !== $keyForSaveQuery)
                || $query->getQuery()->connection !== $connection
                || $query->getQuery()->grammar !== $queryGrammar
                || $query->getQuery()->processor !== $queryProcessor
                || $connection->getQueryGrammar() !== $queryGrammar
                || $connection->getPostProcessor() !== $queryProcessor
                || $query->getQuery()->from !== $table
                || ($expectedWheres !== null && (
                    $query->getQuery()->wheres !== $expectedWheres
                    || $query->getQuery()->getRawBindings()['where'] !== $expectedWhereBindings
                ))
                || ($this->getAttribute('team_id') === null) !== ($teamId === null)
                || (string) $this->getAttribute('team_id') !== (string) $teamId
                || $this->getAttribute('user_id') !== $ownerId) {
                throw new \LogicException('A named write cannot change its resource, tenancy, owner, or physical database writer.');
            }
        };

        $assertIntent();

        if ($authenticatedUser === null) {
            return true;
        }

        $currentUser = auth()->user();

        if ($currentUser !== $authenticatedUser
            || User::connectionCacheIdentity($authenticatedUser->getConnection())
                !== User::connectionCacheIdentity($connection)) {
            throw new \LogicException('A global write cannot change its authenticated actor or database connection.');
        }

        if ($globalWrite) {
            Gate::forUser($authenticatedUser)->authorize('createGlobal', $this);
        }

        if ($authorizeUpdate) {
            Gate::forUser($authenticatedUser)->authorize('update', $this);
        }

        if ($globalWrite && $ownerId !== null && (string) $ownerId !== (string) $authenticatedUser->getKey()) {
            throw new \LogicException('A global resource owner must match the authenticated actor.');
        }

        $assertIntent();

        return true;
    }

    private function ensureDeleteUsesAuthenticatedConnection(): void
    {
        $connectionIdentity = User::connectionCacheIdentity($this->getConnection());

        $authenticatedUser = auth()->user();

        if ($authenticatedUser === null) {
            return;
        }

        if (! $authenticatedUser instanceof User) {
            throw new \LogicException('Only authenticated Aura users may delete resources.');
        }

        if (User::connectionCacheIdentity($authenticatedUser->getConnection()) !== $connectionIdentity) {
            throw new \LogicException(
                'Authenticated actors cannot delete resources on another database connection.',
            );
        }
    }

    /**
     * Run through Laravel's normal Connection path while placing authorization
     * after Builder and Connection callbacks. The scoped dispatcher is removed
     * in finally, and the transaction disables Laravel's unauthorised
     * reconnect-and-retry path.
     *
     * @template TValue
     *
     * @param  callable(int): bool  $authorize
     * @param  callable(): TValue  $persist
     * @return TValue
     */
    private function executeWithFinalConnectionAuthorization(
        Connection $connection,
        PDO $writePdo,
        int $transactionLevel,
        callable $authorize,
        callable $persist,
    ): mixed {
        $operation = new class($authorize)
        {
            private bool $authorizing = false;

            public function __construct(private readonly mixed $authorize) {}

            public function authorize(int $transactionLevel): void
            {
                if ($this->authorizing) {
                    return;
                }

                $this->authorizing = true;

                try {
                    ($this->authorize)($transactionLevel);
                } finally {
                    $this->authorizing = false;
                }
            }
        };
        $execute = function () use ($connection, $writePdo, $transactionLevel, $operation, $persist): mixed {
            $expectedTransactionLevel = $connection->getRawPdo() === $writePdo
                ? $connection->transactionLevel()
                : $transactionLevel;
            $callbacksProperty = new \ReflectionProperty(Connection::class, 'beforeExecutingCallbacks');
            /** @var list<callable> $originalCallbacks */
            $originalCallbacks = $callbacksProperty->getValue($connection);
            $dispatcher = static function (string $query, array $bindings, Connection $executingConnection) use (
                $operation,
                $originalCallbacks,
                $expectedTransactionLevel,
            ): void {
                foreach ($originalCallbacks as $callback) {
                    $callback($query, $bindings, $executingConnection);
                }

                $operation->authorize($expectedTransactionLevel);
            };
            $callbacksProperty->setValue($connection, [$dispatcher]);

            try {
                $operation->authorize($expectedTransactionLevel);

                return $persist();
            } finally {
                /** @var list<callable> $currentCallbacks */
                $currentCallbacks = $callbacksProperty->getValue($connection);
                $callbacksAddedDuringExecution = array_values(array_filter(
                    $currentCallbacks,
                    static fn (callable $callback): bool => $callback !== $dispatcher,
                ));
                $callbacksProperty->setValue(
                    $connection,
                    array_merge($originalCallbacks, $callbacksAddedDuringExecution),
                );
            }
        };

        if ($connection->transactionLevel() > 0) {
            return $execute();
        }

        $operation->authorize($transactionLevel);

        try {
            return $connection->transaction(function () use ($connection, $writePdo, $execute): mixed {
                if ($connection->getRawPdo() !== $writePdo) {
                    throw new \LogicException('A named write cannot change its physical database writer.');
                }

                return $execute();
            }, 1);
        } finally {
            if ($connection->getRawPdo() !== $writePdo) {
                $connection->setPdo($writePdo);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function firstGlobalRecordOnCapturedWriter(Builder $query, array $attributes): ?Model
    {
        $connection = $this->getConnection();
        $writePdo = $connection->getPdo();

        if (! $writePdo instanceof PDO) {
            throw new \LogicException('A named write requires an active physical database writer.');
        }

        $transactionLevel = $connection->transactionLevel();
        $lookup = (clone $query)->where($attributes);
        $queryBuilder = $lookup->getQuery();
        $grammar = $queryBuilder->getGrammar();
        $processor = $queryBuilder->getProcessor();
        $table = $queryBuilder->from;
        $wheres = $queryBuilder->wheres;
        $whereBindings = $queryBuilder->getRawBindings()['where'];
        $authorize = function (int $expectedTransactionLevel) use (
            $connection,
            $writePdo,
            $lookup,
            $grammar,
            $processor,
            $table,
            $wheres,
            $whereBindings,
        ): bool {
            $this->assertPrivilegedConnectionState($connection, $writePdo, $expectedTransactionLevel);
            $queryBuilder = $lookup->getQuery();

            if ($queryBuilder->connection !== $connection
                || $queryBuilder->grammar !== $grammar
                || $queryBuilder->processor !== $processor
                || $connection->getQueryGrammar() !== $grammar
                || $connection->getPostProcessor() !== $processor
                || $queryBuilder->from !== $table
                || $queryBuilder->wheres !== $wheres
                || $queryBuilder->getRawBindings()['where'] !== $whereBindings) {
                throw new \LogicException('A named write cannot change its resource, tenancy, owner, or physical database writer.');
            }

            return true;
        };

        $queryBuilder->applyBeforeQueryCallbacks();

        try {
            return $this->executeWithFinalConnectionAuthorization(
                $connection,
                $writePdo,
                $transactionLevel,
                $authorize,
                fn (): ?Model => $lookup->first(),
            );
        } finally {
            $this->restoreConnectionQueryInfrastructure($connection, $grammar, $processor);
            $this->restorePhysicalWriter($connection, $writePdo, $transactionLevel);
        }
    }

    private function performGlobalInsert(
        Builder $query,
        Connection $connection,
        Grammar $queryGrammar,
        Processor $queryProcessor,
        PDO $writePdo,
        string $table,
        mixed $ownerId,
        ?User $authenticatedUser,
        int|string|null $teamId = null,
        bool $globalWrite = true,
        string $keyName = 'id',
        int $transactionLevel = 0,
    ): bool {
        if ($this->usesUniqueIds()) {
            $this->setUniqueIds();
        }

        if (parent::fireModelEvent('creating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $attributes = $this->getAttributesForInsert();
        $authorize = fn (int $expectedTransactionLevel): bool => $this->authorizePrivilegedPersistence(
            $connection,
            $query,
            $queryGrammar,
            $queryProcessor,
            $writePdo,
            $table,
            $teamId,
            $ownerId,
            $authenticatedUser,
            false,
            $globalWrite,
            $keyName,
            null,
            false,
            $expectedTransactionLevel,
        );

        if ($this->getIncrementing()) {
            $query->getQuery()->applyBeforeQueryCallbacks();
            $this->setAttribute(
                $this->getKeyName(),
                $this->executeWithFinalConnectionAuthorization(
                    $connection,
                    $writePdo,
                    $transactionLevel,
                    $authorize,
                    fn (): mixed => $query->insertGetId($attributes, $this->getKeyName()),
                ),
            );
        } elseif ($attributes !== []) {
            $query->getQuery()->applyBeforeQueryCallbacks();
            $this->executeWithFinalConnectionAuthorization(
                $connection,
                $writePdo,
                $transactionLevel,
                $authorize,
                fn (): bool => $query->insert($attributes),
            );
        } else {
            $authorize($transactionLevel);
        }

        $this->exists = true;
        $this->wasRecentlyCreated = true;
        parent::fireModelEvent('created', false);

        return true;
    }

    private function performGlobalUpdate(
        Builder $query,
        Connection $connection,
        Grammar $queryGrammar,
        Processor $queryProcessor,
        PDO $writePdo,
        string $table,
        mixed $ownerId,
        ?User $authenticatedUser,
        int|string|null $teamId,
        bool $authorizeUpdate,
        bool $globalWrite,
        string $keyName,
        mixed $keyForSaveQuery,
        int $transactionLevel,
    ): bool {
        if (parent::fireModelEvent('updating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirtyForUpdate();
        $saveQuery = $this->setKeysForSaveQuery($query);
        $expectedWheres = $saveQuery->getQuery()->wheres;
        $expectedWhereBindings = $saveQuery->getQuery()->getRawBindings()['where'];
        $authorize = fn (int $expectedTransactionLevel): bool => $this->authorizePrivilegedPersistence(
            $connection,
            $query,
            $queryGrammar,
            $queryProcessor,
            $writePdo,
            $table,
            $teamId,
            $ownerId,
            $authenticatedUser,
            $authorizeUpdate,
            $globalWrite,
            $keyName,
            $keyForSaveQuery,
            true,
            $expectedTransactionLevel,
            $expectedWheres,
            $expectedWhereBindings,
        );

        if ($dirty !== []) {
            $saveQuery->getQuery()->applyBeforeQueryCallbacks();
            $this->executeWithFinalConnectionAuthorization(
                $connection,
                $writePdo,
                $transactionLevel,
                $authorize,
                fn (): int => $saveQuery->update($dirty),
            );
            $this->syncChanges();
            parent::fireModelEvent('updated', false);
        } else {
            $authorize($transactionLevel);
        }

        return true;
    }

    /**
     * Resolve dynamic property access — the single source of truth behind
     * __get. The precedence ladder (documented on the class) is annotated
     * inline below; the control flow is byte-identical to the previous __get
     * body, including the deliberately-kept redundant is_null() guard in
     * step 4.
     *
     * The $key parameter is intentionally untyped to match __get's surface.
     *
     * @param  mixed  $key
     * @return mixed
     */
    private function resolveDynamicAttribute($key)
    {
        // 1. Real Eloquent state: parent::__get resolves a declared attribute,
        //    an accessor, or a loaded/lazy relation for this key.
        $value = parent::__get($key);

        // 2. Any non-null result from (1) wins as-is — including falsy
        //    0/''/false; only a genuinely absent (null) attribute falls
        //    through to the relation/field resolution below.
        if (! is_null($value)) {
            return $value;
        }

        // 3. Relation field slug: return the field's getRelation() result,
        //    coercing ANY falsy value (null/empty) to an empty collection.
        if ($this->getFieldSlugs()->contains($key)) {
            $fieldClass = $this->fieldClassBySlug($key);
            if ($fieldClass->isRelation()) {
                $field = $this->fieldBySlug($key);
                $relation = $fieldClass->getRelation($this, $field);

                return $relation ?: collect();  // Return an empty collection if relation is null
            }
        }

        // 4. Computed field value from the `fields` accessor, if present. The
        //    is_null($value) guard is redundant here (step 2 already returned
        //    on any non-null $value) but is preserved verbatim.
        if (is_null($value) && isset($this->fields[$key])) {
            return $this->fields[$key];
        }

        // 5. Nothing matched: return the (null) $value.
        return $value;
    }

    private function restoreConnectionQueryInfrastructure(
        Connection $connection,
        Grammar $grammar,
        Processor $processor,
    ): void {
        if ($connection->getQueryGrammar() !== $grammar) {
            $connection->setQueryGrammar($grammar);
        }

        if ($connection->getPostProcessor() !== $processor) {
            $connection->setPostProcessor($processor);
        }
    }

    private function restorePhysicalWriter(
        Connection $connection,
        PDO $writePdo,
        int $transactionLevel,
    ): void {
        if ($connection->getRawPdo() !== $writePdo) {
            $connection->setPdo($writePdo);
        }

        if ($connection->transactionLevel() !== 0
            || ($transactionLevel > 0) !== $writePdo->inTransaction()) {
            return;
        }

        $transactionsProperty = new \ReflectionProperty(Connection::class, 'transactions');
        $transactionsProperty->setValue($connection, $transactionLevel);
    }

    private function saveGlobalResource(
        ?User $authenticatedUser = null,
        bool $authorizeUpdate = false,
        bool $trustedOwnerIntent = false,
        array $options = [],
    ): bool {
        return $this->savePrivilegedResource(
            globalWrite: true,
            authenticatedUser: $authenticatedUser,
            authorizeUpdate: $authorizeUpdate,
            trustedOwnerIntent: $trustedOwnerIntent,
            trustedOwnerId: $this->getAttribute('user_id'),
            options: $options,
        );
    }

    private function savePrivilegedResource(
        bool $globalWrite,
        ?User $authenticatedUser = null,
        bool $authorizeUpdate = false,
        bool $trustedOwnerIntent = false,
        int|string|null $trustedOwnerId = null,
        bool $trustedTeamIntent = false,
        int|string|null $trustedTeamId = null,
        array $options = [],
    ): bool {
        $this->mergeAttributesFromCachedCasts();

        $connection = $this->getConnection();
        $query = $this->newModelQuery();
        $queryGrammar = $query->getQuery()->getGrammar();
        $queryProcessor = $query->getQuery()->getProcessor();
        $writePdo = $connection->getPdo();
        $transactionLevel = $connection->transactionLevel();

        if (! $writePdo instanceof PDO) {
            throw new \LogicException('A named write requires an active physical database writer.');
        }

        try {
            $table = $this->getTable();
            $this->prepareFieldAttributesForPersistence(
                globalWrite: $globalWrite,
                trustedOwnerIntent: $trustedOwnerIntent,
                trustedOwnerId: $trustedOwnerId,
                trustedTeamIntent: $trustedTeamIntent,
                trustedTeamId: $trustedTeamId,
            );
            $teamId = $this->getAttribute('team_id');
            $ownerId = $this->getAttribute('user_id');
            $exists = $this->exists;
            $keyName = $this->getKeyName();
            $keyForSaveQuery = $exists ? $this->getKeyForSaveQuery() : null;

            if (parent::fireModelEvent('saving') === false) {
                return false;
            }

            if ($this->exists !== $exists) {
                throw new \LogicException('A named write cannot change whether its resource already exists.');
            }

            $saved = $this->exists
                ? $this->performGlobalUpdate(
                    $query,
                    $connection,
                    $queryGrammar,
                    $queryProcessor,
                    $writePdo,
                    $table,
                    $ownerId,
                    $authenticatedUser,
                    $teamId,
                    $authorizeUpdate,
                    $globalWrite,
                    $keyName,
                    $keyForSaveQuery,
                    $transactionLevel,
                )
                : $this->performGlobalInsert(
                    $query,
                    $connection,
                    $queryGrammar,
                    $queryProcessor,
                    $writePdo,
                    $table,
                    $ownerId,
                    $authenticatedUser,
                    $teamId,
                    $globalWrite,
                    $keyName,
                    $transactionLevel,
                );

            if (! $saved) {
                return false;
            }

            $this->finishSave($options);

            return true;
        } finally {
            $this->restoreConnectionQueryInfrastructure($connection, $queryGrammar, $queryProcessor);
            $this->restorePhysicalWriter($connection, $writePdo, $transactionLevel);
        }
    }

    private function saveSystemResource(
        bool $trustedOwnerIntent = false,
        int|string|null $trustedOwnerId = null,
        bool $trustedTeamIntent = false,
        int|string|null $trustedTeamId = null,
        array $options = [],
    ): bool {
        return $this->savePrivilegedResource(
            globalWrite: false,
            trustedOwnerIntent: $trustedOwnerIntent,
            trustedOwnerId: $trustedOwnerId,
            trustedTeamIntent: $trustedTeamIntent,
            trustedTeamId: $trustedTeamId,
            options: $options,
        );
    }
}

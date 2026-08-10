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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PDO;
use PDOStatement;
use UnitEnum;

use function Illuminate\Support\enum_value;

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

    /**
     * Exact model instances prepared by a named global-write contract.
     *
     * This is validation intent, not an exportable database capability. The
     * captured PDO is used only by the package-owned statement after all model,
     * query-builder, and connection callbacks have returned.
     *
     * @var \WeakMap<self, array{class: class-string<self>, connection: Connection, writePdo: PDO, phase: 'pending'|'saving'|'persisted'}>|null
     */
    private static ?\WeakMap $globalWriteIntents = null;

    /** @var list<array{connection: string, owner_id: int|string|null}> */
    private static array $trustedOwnerContexts = [];

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

        return static::withinTrustedOwnerFromAttributes(
            $attributes,
            fn (): bool => $this->update($attributes),
            $this->getConnection(),
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

        return static::withinTrustedOwnerFromAttributes(
            $attributes,
            fn (): static => static::ensureStaticResource(
                $resource->newQueryWithoutScopes()->create($attributes),
            ),
            $resource->getConnection(),
        );
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

        return static::withinTrustedOwnerFromAttributes(
            $attributes,
            fn (): static => TeamScope::forTeam(
                $teamId,
                fn (): static => static::ensureStaticResource(
                    $resource->newQueryWithoutScopes()->create($attributes),
                ),
                $resource->getConnection(),
            ),
            $resource->getConnection(),
        );
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

        return static::createGlobalRecord($attributes, $resource->getConnection());
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

        return static::withinTrustedOwnerFromAttributes(
            $attributes,
            fn (): static => static::createGlobalRecord($attributes, $resource->getConnection()),
            $resource->getConnection(),
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

        return static::withinTrustedOwnerFromAttributes(
            array_merge($attributes, $values),
            fn (): static => static::firstOrCreateGlobalRecord($attributes, $values, $resource),
            $resource->getConnection(),
        );
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

        return static::withinTrustedOwnerFromAttributes(
            $attributes,
            fn (): bool => TeamScope::forTeam($teamId, function () use ($attributes, $teamId): bool {
                $this->fill($attributes);
                $this->setAttribute('team_id', $teamId);

                return $this->save();
            }, $this->getConnection()),
            $this->getConnection(),
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

        return static::withinGlobalWrite($this, fn (): bool => $this->save());
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

    /**
     * Save a model while preventing a callback from reusing a pending global
     * write intent on the same instance. Post-persistence callbacks retain
     * Laravel's normal ability to save the model again as an ordinary update.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = [])
    {
        $intent = self::$globalWriteIntents[$this] ?? null;

        if ($intent === null || $intent['phase'] === 'persisted') {
            return parent::save($options);
        }

        if ($intent['phase'] === 'saving') {
            throw new \LogicException('A global Resource save cannot be re-entered from a callback.');
        }

        $intent['phase'] = 'saving';
        self::$globalWriteIntents[$this] = $intent;

        $saved = parent::save($options);

        if ($saved && isset(self::$globalWriteIntents[$this])) {
            $intent = self::$globalWriteIntents[$this];
            $intent['phase'] = 'persisted';
            self::$globalWriteIntents[$this] = $intent;
        }

        return $saved;
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

        return static::withinTrustedOwnerFromAttributes(
            array_merge($attributes, $values),
            fn (): static => static::updateOrCreateGlobalRecord($attributes, $values, $resource),
            $resource->getConnection(),
        );
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
    ): static {
        static::ensureGlobalWriteIsSupported();

        $resource = static::resourceModelOnConnection($connection);

        return static::createGlobalResourceInstance($attributes, $resource);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected static function createGlobalResourceInstance(array $attributes, Resource $resource): static
    {
        $attributes['team_id'] = null;
        $instance = static::ensureStaticResource($resource->newInstance($attributes));

        return static::withinGlobalWrite($instance, function () use ($instance): static {
            $instance->save();

            return $instance;
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
        $existing = (clone $query)->where($attributes)->first();

        if ($existing !== null) {
            return static::ensureStaticResource($existing);
        }

        $create = fn (): static => static::createGlobalResourceInstance(
            array_merge($attributes, $values),
            $resource,
        );

        try {
            return $resource->getConnection()->transactionLevel() > 0
                ? $resource->getConnection()->transaction($create)
                : $create();
        } catch (UniqueConstraintViolationException $exception) {
            $existing = (clone $query)->where($attributes)->first();

            return $existing !== null
                ? static::ensureStaticResource($existing)
                : throw $exception;
        }
    }

    protected static function hasTrustedOwnerContextForConnection(Connection $connection): bool
    {
        if (self::$trustedOwnerContexts === []) {
            return false;
        }

        $context = self::$trustedOwnerContexts[array_key_last(self::$trustedOwnerContexts)];

        return $context['connection'] === User::connectionCacheIdentity($connection);
    }

    protected static function isOwnerWriteAuthorized(
        int|string $ownerId,
        Connection $connection,
    ): bool {
        if (self::$trustedOwnerContexts !== []) {
            $context = self::$trustedOwnerContexts[array_key_last(self::$trustedOwnerContexts)];

            return $context['connection'] === User::connectionCacheIdentity($connection)
                && $context['owner_id'] !== null
                && (string) $context['owner_id'] === (string) $ownerId;
        }

        $actor = auth()->user();

        return $actor instanceof User
            && User::connectionCacheIdentity($actor->getConnection()) === User::connectionCacheIdentity($connection)
            && (string) $actor->getKey() === (string) $ownerId;
    }

    /**
     * Perform a global insert only after Laravel's creating callback has
     * completed. The captured writer is never placed in callback-visible
     * state.
     */
    protected function performInsert(Builder $query)
    {
        if (! $this->hasPendingGlobalWriteIntent()) {
            return parent::performInsert($query);
        }

        if ($this->usesUniqueIds()) {
            $this->setUniqueIds();
        }

        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $attributes = $this->getAttributesForInsert();

        if ($this->getIncrementing()) {
            $id = $this->executeGlobalInsert($query, $attributes, $this->getKeyName());
            $this->completeGlobalWritePersistence();
            $this->setAttribute($this->getKeyName(), $id);
        } else {
            if (empty($attributes)) {
                $this->completeGlobalWritePersistence();

                return true;
            }

            $this->executeGlobalInsert($query, $attributes);
            $this->completeGlobalWritePersistence();
        }

        $this->exists = true;
        $this->wasRecentlyCreated = true;
        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Perform a global update only after Laravel's updating callback has
     * completed. Ordinary Resource saves keep Laravel's native implementation.
     */
    protected function performUpdate(Builder $query)
    {
        if (! $this->hasPendingGlobalWriteIntent()) {
            return parent::performUpdate($query);
        }

        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirtyForUpdate();

        if (count($dirty) > 0) {
            $this->executeGlobalUpdate($this->setKeysForSaveQuery($query), $dirty);
            $this->completeGlobalWritePersistence();
            $this->syncChanges();
            $this->fireModelEvent('updated', false);
        } else {
            $this->completeGlobalWritePersistence();
        }

        return true;
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
            static::withinGlobalWrite($instance, fn (): bool => $instance->save());
        }

        return $instance;
    }

    /**
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    protected static function withinGlobalWrite(Resource $resource, callable $callback): mixed
    {
        self::$globalWriteIntents ??= new \WeakMap;

        if (count(self::$globalWriteIntents) > 0) {
            throw new \LogicException('A nested global Resource write cannot be started from a callback.');
        }

        $connection = $resource->getConnection();
        $writePdo = $connection->getPdo();

        if (! $writePdo instanceof PDO) {
            throw new \LogicException('A global write requires an active physical database writer.');
        }

        self::$globalWriteIntents[$resource] = [
            'class' => $resource::class,
            'connection' => $connection,
            'writePdo' => $writePdo,
            'phase' => 'pending',
        ];

        try {
            return $callback();
        } finally {
            unset(self::$globalWriteIntents[$resource]);
        }
    }

    /**
     * @template TValue
     *
     * @param  array<string, mixed>  $attributes
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    final protected static function withinTrustedOwnerFromAttributes(
        array $attributes,
        callable $callback,
        ?Connection $connection = null,
    ): mixed {
        if (! array_key_exists('user_id', $attributes)) {
            return $callback();
        }

        $connection ??= static::resourceModelOnConnection()->getConnection();
        self::$trustedOwnerContexts[] = [
            'connection' => User::connectionCacheIdentity($connection),
            'owner_id' => $attributes['user_id'],
        ];

        try {
            return $callback();
        } finally {
            array_pop(self::$trustedOwnerContexts);
        }
    }

    private function assertGlobalWriteIntentMatchesPhysicalWriter(): void
    {
        $intent = self::$globalWriteIntents[$this] ?? null;

        if ($intent === null || ! self::globalWriteIntentMatches($this, $intent)) {
            throw new \LogicException('A global write cannot change resource or physical database writer.');
        }
    }

    private function completeGlobalWritePersistence(): void
    {
        if (! isset(self::$globalWriteIntents[$this])) {
            return;
        }

        $intent = self::$globalWriteIntents[$this];
        $intent['phase'] = 'persisted';
        self::$globalWriteIntents[$this] = $intent;
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
     * Execute one package-owned persistence statement on the physical writer
     * captured before model callbacks. Connection callbacks run first, while no
     * Aura global-write authority is exposed, and the writer identity is then
     * revalidated immediately before PDO execution.
     *
     * @param  list<mixed>  $bindings
     */
    private function executeAgainstCapturedGlobalWriter(
        Connection $connection,
        string $sql,
        array $bindings,
        string $operation,
        ?string $sequence = null,
    ): mixed {
        $this->invokeBeforeExecutingCallbacks($connection, $sql, $bindings);
        $this->assertGlobalWriteIntentMatchesPhysicalWriter();

        $intent = self::$globalWriteIntents[$this];
        $writePdo = $intent['writePdo'];
        $preparedBindings = $connection->prepareBindings($bindings);
        $startedAt = microtime(true);

        $callback = function (string $_sql, array $_bindings) use (
            $connection,
            $operation,
            $preparedBindings,
            $sequence,
            $sql,
            $writePdo,
        ): mixed {
            if ($connection->pretending()) {
                return $operation === 'update' ? 0 : true;
            }

            $statement = $writePdo->prepare($sql);

            if (! $statement instanceof PDOStatement) {
                throw new \RuntimeException('Unable to prepare the global Resource persistence statement.');
            }

            $connection->bindValues($statement, $preparedBindings);
            $executed = $statement->execute();

            if ($operation === 'update') {
                return $statement->rowCount();
            }

            if ($operation === 'insert') {
                return $executed;
            }

            if ($connection->getDriverName() === 'pgsql') {
                return $statement->fetchColumn();
            }

            if ($connection->getDriverName() === 'sqlsrv'
                && $connection->getConfig('odbc') === true) {
                $identity = $writePdo->query(
                    'SELECT CAST(COALESCE(SCOPE_IDENTITY(), @@IDENTITY) AS int) AS insertid',
                );

                return $identity instanceof PDOStatement
                    ? $identity->fetchColumn()
                    : throw new \RuntimeException('Unable to retrieve the inserted Resource ID.');
            }

            return $writePdo->lastInsertId($sequence);
        };

        $result = $this->invokeRunQueryCallback($connection, $sql, $bindings, $callback);
        $this->completeGlobalWritePersistence();
        $connection->recordsHaveBeenModified(
            $operation === 'update' ? $result > 0 : (bool) $result,
        );
        $connection->logQuery(
            $sql,
            $bindings,
            round((microtime(true) - $startedAt) * 1000, 2),
        );

        return is_numeric($result) ? (int) $result : $result;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function executeGlobalInsert(
        Builder $query,
        array $attributes,
        ?string $sequence = null,
    ): mixed {
        $baseQuery = $query->toBase();
        $baseQuery->applyBeforeQueryCallbacks();
        $grammar = $baseQuery->getGrammar();
        $sql = $sequence === null
            ? $grammar->compileInsert($baseQuery, $attributes)
            : $grammar->compileInsertGetId($baseQuery, $attributes, $sequence);
        $bindings = $baseQuery->cleanBindings(Arr::flatten([$attributes], 1));

        return $this->executeAgainstCapturedGlobalWriter(
            $baseQuery->getConnection(),
            $sql,
            $bindings,
            $sequence === null ? 'insert' : 'insert-id',
            $sequence,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function executeGlobalUpdate(Builder $query, array $values): int
    {
        $values = $this->prepareGlobalUpdateValues($query, $values);
        $baseQuery = $query->toBase();
        $baseQuery->applyBeforeQueryCallbacks();
        $values = collect($values)->map(
            static fn (mixed $value): mixed => $value instanceof UnitEnum
                ? enum_value($value)
                : $value,
        )->all();
        $grammar = $baseQuery->getGrammar();
        $sql = $grammar->compileUpdate($baseQuery, $values);
        $bindings = $baseQuery->cleanBindings(
            $grammar->prepareBindingsForUpdate($baseQuery->bindings, $values),
        );

        return $this->executeAgainstCapturedGlobalWriter(
            $baseQuery->getConnection(),
            $sql,
            $bindings,
            'update',
        );
    }

    /**
     * @param  array{class: class-string<self>, connection: Connection, writePdo: PDO, phase: 'pending'|'saving'|'persisted'}  $intent
     */
    private static function globalWriteIntentMatches(Resource $resource, array $intent): bool
    {
        $connection = $resource->getConnection();

        return $intent['class'] === $resource::class
            && $intent['connection'] === $connection
            && $intent['writePdo'] === $connection->getRawPdo();
    }

    private static function hasGlobalWriteValidationIntent(Resource $resource): bool
    {
        $intent = self::$globalWriteIntents[$resource] ?? null;

        return $intent !== null
            && $intent['phase'] === 'saving'
            && self::globalWriteIntentMatches($resource, $intent);
    }

    private function hasPendingGlobalWriteIntent(): bool
    {
        $intent = self::$globalWriteIntents[$this] ?? null;

        return $intent !== null && $intent['phase'] === 'saving';
    }

    /**
     * @param  list<mixed>  $bindings
     */
    private function invokeBeforeExecutingCallbacks(
        Connection $connection,
        string $sql,
        array $bindings,
    ): void {
        $property = new \ReflectionProperty(Connection::class, 'beforeExecutingCallbacks');
        $callbacks = $property->getValue($connection);

        foreach ($callbacks as $callback) {
            $callback($sql, $bindings, $connection);
        }
    }

    /**
     * Preserve Laravel's driver-specific QueryException conversion without
     * routing the statement back through callback-visible Connection::run().
     *
     * @param  list<mixed>  $bindings
     */
    private function invokeRunQueryCallback(
        Connection $connection,
        string $sql,
        array $bindings,
        \Closure $callback,
    ): mixed {
        $method = new \ReflectionMethod(Connection::class, 'runQueryCallback');

        return $method->invoke($connection, $sql, $bindings, $callback);
    }

    /**
     * Match Eloquent\Builder::update() timestamp qualification without running
     * the statement through callback-visible Connection::run().
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function prepareGlobalUpdateValues(Builder $query, array $values): array
    {
        if (! $this->usesTimestamps() || is_null($this->getUpdatedAtColumn())) {
            return $values;
        }

        $column = $this->getUpdatedAtColumn();

        if (! array_key_exists($column, $values)) {
            $values[$column] = $this->freshTimestampString();
        }

        $segments = preg_split('/\s+as\s+/i', $query->getQuery()->from);
        $qualifiedColumn = array_last($segments).'.'.$column;
        $values[$qualifiedColumn] = Arr::get($values, $qualifiedColumn, $values[$column]);
        unset($values[$column]);

        return $values;
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
}

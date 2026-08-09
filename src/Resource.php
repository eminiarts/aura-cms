<?php

namespace Aura\Base;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\TypeScope;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InitialPostFields;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Aura\Base\Traits\SaveFieldAttributes;
use Aura\Base\Traits\SaveMetaFields;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

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
     * Process-local depth for the narrow global-write invariant bypass.
     * Always entered through a named creation/promotion contract and unwound
     * in finally, including when model events throw an Error.
     */
    private static int $globalWriteDepth = 0;

    /** @var list<int|string|null> */
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
    public static function createForOwnerForSystem(int|string $ownerId, array $attributes = []): static
    {
        $attributes['user_id'] = $ownerId;

        return static::withinTrustedOwnerFromAttributes(
            $attributes,
            fn (): static => app(static::class)->newQueryWithoutScopes()->create($attributes),
        );
    }

    /**
     * Create a team-owned row from trusted infrastructure.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createForTeamForSystem(int|string $teamId, array $attributes = []): static
    {
        static::ensureTeamWriteIsSupported();

        $attributes['team_id'] = $teamId;

        return static::withinTrustedOwnerFromAttributes(
            $attributes,
            fn (): static => TeamScope::forTeam(
                $teamId,
                fn (): static => app(static::class)->newQueryWithoutScopes()->create($attributes),
            ),
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

        if ($connection === null && $authenticatedUser instanceof Model) {
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

        return static::withinTrustedOwnerFromAttributes(
            array_merge($attributes, $values),
            fn (): static => static::withinGlobalWrite(function () use ($attributes, $connection, $values): static {
                $attributes['team_id'] = null;
                unset($values['team_id']);

                $model = clone app(static::class);

                if ($connection) {
                    $model->setConnection($connection->getName());
                }

                return $model->newQueryWithoutScopes()->firstOrCreate($attributes, $values);
            }),
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

    public static function isGlobalWriteInProgress(): bool
    {
        return self::$globalWriteDepth > 0;
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
        });
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
            }),
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

        return static::withinGlobalWrite(function () use ($attributes): bool {
            $this->fill($attributes);
            $this->setAttribute('team_id', null);

            return $this->save();
        });
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

        return static::withinTrustedOwnerFromAttributes(
            array_merge($attributes, $values),
            fn (): static => static::withinGlobalWrite(function () use ($attributes, $connection, $values): static {
                $attributes['team_id'] = null;
                unset($values['team_id']);

                $model = clone app(static::class);

                if ($connection) {
                    $model->setConnection($connection->getName());
                }

                return $model->newQueryWithoutScopes()->updateOrCreate($attributes, $values);
            }),
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

        return static::withinGlobalWrite(function () use ($attributes, $connection): static {
            $attributes['team_id'] = null;

            $model = clone app(static::class);

            if ($connection) {
                $model->setConnection($connection->getName());
            }

            return $model->newQueryWithoutScopes()->create($attributes);
        });
    }

    protected static function ensureGlobalWriteIsSupported(): void
    {
        if (! config('aura.teams') || ! static::sharesRecordsAcrossTeams()) {
            throw new \LogicException('Global writes require teams and an opted-in shared resource.');
        }
    }

    protected static function ensureTeamWriteIsSupported(): void
    {
        if (! config('aura.teams')) {
            throw new \LogicException('Team writes require teams to be enabled.');
        }
    }

    protected static function isOwnerWriteAuthorized(int|string $ownerId): bool
    {
        if (self::$trustedOwnerContexts !== []) {
            $trustedOwnerId = self::$trustedOwnerContexts[array_key_last(self::$trustedOwnerContexts)];

            return $trustedOwnerId !== null && (string) $trustedOwnerId === (string) $ownerId;
        }

        $actorId = auth()->id();

        return $actorId !== null && (string) $actorId === (string) $ownerId;
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
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    protected static function withinGlobalWrite(callable $callback): mixed
    {
        self::$globalWriteDepth++;

        try {
            return $callback();
        } finally {
            self::$globalWriteDepth--;
        }
    }

    /**
     * @template TValue
     *
     * @param  array<string, mixed>  $attributes
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    final protected static function withinTrustedOwnerFromAttributes(array $attributes, callable $callback): mixed
    {
        if (! array_key_exists('user_id', $attributes)) {
            return $callback();
        }

        self::$trustedOwnerContexts[] = $attributes['user_id'];

        try {
            return $callback();
        } finally {
            array_pop(self::$trustedOwnerContexts);
        }
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

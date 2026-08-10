<?php

namespace Aura\Base;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueContract;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\TypeScope;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InitialPostFields;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Aura\Base\Traits\SaveFieldAttributes;
use Aura\Base\Traits\SaveMetaFields;
use Closure;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

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
 * @property array<string, mixed> $metaFields Pending meta values awaiting persistence.
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

    /**
     * Query-builder writes that can be reached through Model::__call without
     * passing through this model's save boundary.
     *
     * @var array<int, string>
     */
    private const GUARDED_FORWARDED_WRITE_METHODS = [
        'insert',
        'insertGetId',
        'insertOrIgnore',
        'insertOrIgnoreReturning',
        'insertOrIgnoreUsing',
        'insertUsing',
        'updateFrom',
        'updateOrInsert',
        'upsert',
    ];

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

    protected int $fieldDefinitionGeneration = -1;

    protected ?string $fieldDefinitionStateKey = null;

    protected bool $fieldDefinitionStateReady = false;

    /**
     * Active hydration context for the backward-compatible getMeta() and
     * resolveFieldValue() entry points.
     */
    protected FieldValueContext $fieldValueContext = FieldValueContext::Model;

    protected $fillable = ['title', 'content', 'type', 'status', 'fields', 'slug', 'user_id', 'parent_id', 'order', 'team_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['meta'];

    /**
     * Provider-managed slugs absent from the active field definition.
     *
     * @var array<int, string>
     */
    protected array $inactiveProviderFieldSlugs = [];

    /**
     * Complete provider-managed slug union known by this model instance.
     *
     * @var array<int, string>
     */
    protected array $managedProviderFieldSlugs = [];

    protected array $metaFields = [];

    /**
     * Per-instance cache of the normalized meta map (see getMeta()).
     *
     * @var array<string, Collection>
     */
    protected array $normalizedMetaCache = [];

    /**
     * Physical fields currently passing through Aura's write adapter.
     *
     * @var array<string, true>
     */
    protected array $normalizingPhysicalFieldValues = [];

    /**
     * Physical values copied into the packed fields payload from Eloquent's
     * raw attributes. These values have already completed the write pipeline.
     *
     * @var array<string, true>
     */
    protected array $packedPhysicalFieldValues = [];

    protected bool $readingAttributeState = false;

    /**
     * Input-field slugs used to derive this instance's current fillable state.
     *
     * @var array<int, string>
     */
    protected array $resolvedInputFieldSlugs = [];

    protected bool $synchronizingFieldDefinitionState = false;

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
     * Inactive provider state is kept off Eloquent's persistence and native
     * serialization surfaces until its defining context becomes active again.
     *
     * @var array<string, mixed>
     */
    private array $quarantinedProviderFieldState = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct();

        $this->baseFillable = parent::getFillable();
        $this->fieldDefinitionStateReady = true;

        $this->ensureFieldDefinitionState();

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

        $this->fill($attributes);
    }

    public function __call($method, $parameters)
    {
        $isGuardedForwardedWrite = is_string($method)
            && in_array($method, self::GUARDED_FORWARDED_WRITE_METHODS, true);

        if ($this->getFieldSlugs()->contains($method)) {

            $fieldClass = $this->fieldClassBySlug($method);

            if ($fieldClass->isRelation()) {

                $field = $this->fieldBySlug($method);

                return $fieldClass->relationship($this, $field);
            }
        }

        // Default behavior for methods not handled dynamically
        if ($isGuardedForwardedWrite) {
            $parameters = $this->guardForwardedPersistenceCall($method, $parameters);

            return $this->withoutInactiveAutomaticTimestamps(
                fn (): mixed => parent::__call($method, $parameters),
                $method === 'upsert'
                    ? [$this->getCreatedAtColumn(), $this->getUpdatedAtColumn()]
                    : [],
            );
        }

        return parent::__call($method, $parameters);
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

    public function __set($key, $value)
    {
        if ($key === 'metaFields') {
            $this->ensureFieldDefinitionState();
            $this->metaFields = $value;
            $this->quarantineInactiveProviderFieldState(overwriteExisting: true);

            return;
        }

        parent::__set($key, $value);
    }

    /**
     * Prepare a secret-free native PHP representation.
     *
     * @return array<int, string>
     */
    public function __sleep()
    {
        $this->ensureFieldDefinitionState();
        $this->quarantineInactiveProviderFieldState();

        return array_values(array_diff(
            parent::__sleep(),
            ['quarantinedProviderFieldState'],
        ));
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function &__get($key)
    {
        if ($key === 'metaFields') {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();

            return $this->metaFields;
        }

        $value = $this->resolveDynamicAttribute($key);

        return $value;
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
        $this->ensureFieldDefinitionState();
        $this->fieldsAttributeCache = null;
        $this->normalizedMetaCache = [];

        if ($this->usesMeta()) {
            $this->load('meta'); // This will refresh only the 'meta' relationship
        }

    }

    public function clearPackedPhysicalFieldValues(): void
    {
        $this->packedPhysicalFieldValues = [];
    }

    public function consumePhysicalFieldPacked(string $slug): bool
    {
        $packed = isset($this->packedPhysicalFieldValues[$slug]);

        unset($this->packedPhysicalFieldValues[$slug]);

        return $packed;
    }

    public function decrementOrFail(string $column, float|int $amount = 1, array $extra = []): int|false
    {
        return $this->getConnection()->transaction(
            fn (): int|false => $this->incrementOrDecrement($column, $amount, $extra, 'decrement'),
        );
    }

    /**
     * Return only accessors that are visible in the active provider context.
     *
     * @return array<int, string>
     */
    public function getAppends(): array
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): array => array_values(array_diff(
                parent::getAppends(),
                $this->inactiveProviderFieldSlugs,
            )),
        );
    }

    /**
     * Retrieve an attribute after synchronizing dynamic field state for the
     * active provider context.
     */
    public function getAttribute($key)
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): mixed => $this->isInactiveProviderFieldSlug($key)
                ? null
                : $this->withoutInactiveProviderFieldValue($key, parent::getAttribute($key)),
        );
    }

    /**
     * Return attributes after synchronizing definition-derived instance state.
     *
     * @return array<string, mixed>
     */
    public function getAttributes()
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): array => $this->withoutInactiveProviderFieldState(parent::getAttributes()),
        );
    }

    /**
     * Retrieve a plain attribute after synchronizing dynamic field state for
     * the active provider context.
     */
    public function getAttributeValue($key)
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): mixed => $this->isInactiveProviderFieldSlug($key)
                ? null
                : $this->withoutInactiveProviderFieldValue($key, parent::getAttributeValue($key)),
        );
    }

    public function getBulkActions()
    {
        return $this->bulkActions;
    }

    /**
     * Return changed attributes visible in the active provider context.
     *
     * @return array<string, mixed>
     */
    public function getChanges()
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): array => $this->withoutInactiveProviderFieldState(parent::getChanges()),
        );
    }

    public function getFieldsAttribute()
    {
        $this->ensureFieldDefinitionState();

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

    /**
     * @return array<string>
     */
    public function getFillable()
    {
        $this->ensureFieldDefinitionState();

        return parent::getFillable();
    }

    public function getMeta($key = null)
    {
        $this->ensureFieldDefinitionState();
        $this->quarantineInactiveProviderFieldState();
        $context = $this->fieldValueContext;

        if ($this->isInactiveProviderFieldSlug($key)) {
            return $key === null ? collect() : null;
        }

        if ($this->usesCustomTable() && ! $this->usesMeta()) {
            return collect();
        }

        if ($this->usesMeta() && optional($this)->meta && ! is_string($this->meta)) {

            // Build (and cache) the normalized meta map once per instance. The
            // pluck/cast/map scan is otherwise repeated for every displayed
            // cell. The cache is invalidated whenever the meta relation is
            // replaced (setRelation/unsetRelation) or fields are cleared.
            if (! isset($this->normalizedMetaCache[$context->value])) {
                $meta = $this->meta
                    ->pluck('value', 'key')
                    ->except($this->inactiveProviderFieldSlugs);

                // Cast Attributes
                $meta = $meta->map(function ($meta, $key) use ($context) {
                    $field = $this->fieldBySlug($key);

                    $class = $this->fieldClassBySlug($key);

                    if ($class instanceof FieldValueContract) {
                        return $class->hydrateFromStorage(
                            $meta,
                            is_array($field) ? $field : [],
                            $this,
                            FieldValueStorage::Meta,
                            $context,
                        );
                    }

                    if ($class && method_exists($class, 'get')) {
                        return $class->get($class, $meta, $field);
                    }

                    return $meta;
                });

                $this->normalizedMetaCache[$context->value] = $meta;
            }

            if ($key !== null) {
                return $this->normalizedMetaCache[$context->value][$key] ?? null;
            }

            return $this->normalizedMetaCache[$context->value];
        }

        return collect();
    }

    public function getMetaInContext($key, FieldValueContext $context): mixed
    {
        $previousContext = $this->fieldValueContext;
        $this->fieldValueContext = $context;

        try {
            return $this->getMeta($key);
        } finally {
            $this->fieldValueContext = $previousContext;
        }
    }

    /**
     * Return original attributes visible in the active provider context.
     *
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function getOriginal($key = null, $default = null)
    {
        return $this->readAfterSynchronizingFieldDefinition(function () use ($key, $default): mixed {
            if ($this->isInactiveProviderFieldStatePath($key)) {
                return value($default);
            }

            $original = parent::getOriginal($key, $default);

            return $key === null
                ? $this->withoutInactiveProviderFieldState($original)
                : $this->withoutInactiveProviderFieldValue($key, $original);
        });
    }

    /**
     * Return the previous save snapshot visible in the active provider context.
     *
     * @return array<string, mixed>
     */
    public function getPrevious()
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): array => $this->withoutInactiveProviderFieldState(parent::getPrevious()),
        );
    }

    /**
     * Return raw original attributes visible in the active provider context.
     *
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function getRawOriginal($key = null, $default = null)
    {
        return $this->readAfterSynchronizingFieldDefinition(function () use ($key, $default): mixed {
            if ($this->isInactiveProviderFieldStatePath($key)) {
                return value($default);
            }

            $original = parent::getRawOriginal($key, $default);

            return $key === null
                ? $this->withoutInactiveProviderFieldState($original)
                : $this->withoutInactiveProviderFieldValue($key, $original);
        });
    }

    public function getRelation($relation)
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): mixed => $this->isInactiveProviderFieldSlug($relation)
                ? null
                : parent::getRelation($relation),
        );
    }

    public function getRelations()
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): array => parent::getRelations(),
        );
    }

    public function getRelationValue($key)
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): mixed => $this->isInactiveProviderFieldSlug($key)
                ? null
                : parent::getRelationValue($key),
        );
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
        $this->ensureFieldDefinitionState();

        return $this->tableDisplayCache[$slug] ?? null;
    }

    /**
     * Determine whether an attribute exists after synchronizing dynamic field
     * state for the active provider context.
     */
    public function hasAttribute($key)
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): bool => ! $this->isInactiveProviderFieldSlug($key)
                && parent::hasAttribute($key),
        );
    }

    public function hasTableDisplayValue(string $slug): bool
    {
        $this->ensureFieldDefinitionState();

        return array_key_exists($slug, $this->tableDisplayCache);
    }

    public function hydrateFieldValueInContext(
        string $slug,
        mixed $value,
        FieldValueContext $context,
    ): mixed {
        if ($value === null) {
            return null;
        }

        $field = $this->fieldBySlug($slug);
        $fieldClass = $this->fieldClassBySlug($slug);
        $storage = $this->isTableField($slug)
            ? FieldValueStorage::Physical
            : FieldValueStorage::Meta;

        if ($fieldClass instanceof FieldValueContract) {
            return $fieldClass->hydrateFromStorage(
                $value,
                is_array($field) ? $field : [],
                $this,
                $storage,
                $context,
            );
        }

        if ($fieldClass && method_exists($fieldClass, 'get')) {
            return $fieldClass->get($fieldClass, $value, $field);
        }

        return $value;
    }

    public function incrementOrFail(string $column, float|int $amount = 1, array $extra = []): int|false
    {
        return $this->getConnection()->transaction(
            fn (): int|false => $this->incrementOrDecrement($column, $amount, $extra, 'increment'),
        );
    }

    public function isBaseFillable($key)
    {
        return in_array($key, $this->baseFillable);
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

    public function markPhysicalFieldAsPacked(string $slug): void
    {
        $this->packedPhysicalFieldValues[$slug] = true;
    }

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
    }

    public function refresh()
    {
        $this->ensureFieldDefinitionState();
        $this->quarantineInactiveProviderFieldState();

        if (! $this->exists) {
            return $this;
        }

        $this->discardQuarantinedAttributeStateForRefresh();

        $model = parent::refresh();

        $this->quarantineInactiveProviderFieldState();
        $this->synchronizeQuarantinedOriginalAfterRefresh();

        return $model;
    }

    public function relationLoaded($key)
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): bool => ! $this->isInactiveProviderFieldSlug($key)
                && parent::relationLoaded($key),
        );
    }

    public function relationsToArray()
    {
        return $this->readAfterSynchronizingFieldDefinition(
            fn (): array => $this->withoutInactiveProviderFieldState(parent::relationsToArray()),
        );
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
        $context = $this->fieldValueContext;
        $meta ??= $this->getMeta();

        $key = $slug;
        $value = null;

        $class = $this->fieldClassBySlug($key);
        $field = $this->fieldBySlug($key);

        if ($class && method_exists($class, 'isRelation') && $class->isRelation($field) && method_exists($class, 'get') && $field['type'] != 'Aura\\Base\\Fields\\Roles') {
            return $class->get($class, $this->{$key}, $field);
        }

        // Deliberately meta-blind probe: hasAttribute() identifies raw columns,
        // accessors, and casts without interpreting a null result as absence.
        // The value returned by Eloquent is authoritative even when it is null;
        // falling back to a raw legacy sentinel would bypass its getter/cast.
        if ($class instanceof FieldValueContract && $this->hasAttribute($key)) {
            return $class->hydrateFromStorage(
                parent::__get($key),
                is_array($field) ? $field : [],
                $this,
                FieldValueStorage::Physical,
                $context,
            );
        }

        if ($class && $this->hasAttribute($key) && method_exists($class, 'get')) {
            return $class->get($class, parent::__get($key), $field);
        }

        if ($this->hasAttribute($key)) {
            return parent::__get($key);
        }

        $method = 'get'.Str::studly($key).'Field';

        if (method_exists($this, $method)) {
            return $this->{$method}($value);
        }

        if (optional($field)['polymorphic_relation'] === false && optional($field)['multiple'] === false) {
            return isset($meta[$key]) ? [$meta[$key]] : [];
        }

        return $meta[$key] ?? $value;
    }

    public function resolveFieldValueInContext(
        string $slug,
        FieldValueContext $context,
        $meta = null,
    ): mixed {
        $previousContext = $this->fieldValueContext;
        $this->fieldValueContext = $context;

        try {
            return $this->resolveFieldValue($slug, $meta);
        } finally {
            $this->fieldValueContext = $previousContext;
        }
    }

    /**
     * @return HasMany
     */
    public function revision()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('post_type', 'revision');
    }

    public function save(array $options = []): bool
    {
        return $this->persistWithoutInactiveProviderFieldState(
            fn (): bool => parent::save($options),
        );
    }

    public function saveMetaFields(array $metaFields): void
    {
        $this->ensureFieldDefinitionState();

        foreach ($metaFields as $slug => $value) {
            if ($this->isInactiveProviderFieldSlug($slug)) {
                $this->quarantinedProviderFieldState['metaFields'][$slug] = $value;

                continue;
            }

            $this->metaFields[$slug] = $value;
        }
    }

    public function saveOrIgnore(array $options = [], array|string|null $uniqueBy = null): bool
    {
        return $this->persistWithoutInactiveProviderFieldState(
            fn (): bool => parent::saveOrIgnore($options, $uniqueBy),
        );
    }

    /**
     * Compose field normalization with Eloquent's native write pipeline.
     *
     * Aura receives submitted values first. Its normalized result then passes
     * through the model mutator/cast exactly once before reaching the database.
     * Meta-backed fields are normalized later by SaveMetaFields.
     *
     * @param  string  $key
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (is_string($key)
            && ! isset($this->normalizingPhysicalFieldValues[$key])
            && $this->isTableField($key)) {
            $field = $this->fieldBySlug($key);
            $fieldClass = $this->fieldClassBySlug($key);

            if ($fieldClass) {
                $this->normalizingPhysicalFieldValues[$key] = true;

                try {
                    if (isset($field['set']) && $field['set'] instanceof Closure) {
                        $value = ($field['set'])($this, $field, $value);
                    }

                    if ($fieldClass instanceof FieldValueContract) {
                        $value = $fieldClass->normalizeForStorage(
                            $value,
                            is_array($field) ? $field : [],
                            $this,
                            FieldValueStorage::Physical,
                        );
                    } elseif (method_exists($fieldClass, 'set')) {
                        $value = $fieldClass->set($this, $field, $value);
                    }
                } finally {
                    unset($this->normalizingPhysicalFieldValues[$key]);
                }
            }
        }

        $model = parent::setAttribute($key, $value);

        if ($this->fieldDefinitionStateReady) {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState(overwriteExisting: true);
        }

        return $model;
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->clearPackedPhysicalFieldValues();
        parent::setRawAttributes($attributes, false);

        if ($sync) {
            $this->original = $this->attributes;
        }

        if ($this->fieldDefinitionStateReady) {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();
        }

        return $this;
    }

    public function setRelation($relation, $value)
    {
        if ($relation === 'meta') {
            $this->normalizedMetaCache = [];
            $this->fieldsAttributeCache = null;
        }

        $model = parent::setRelation($relation, $value);

        if ($this->fieldDefinitionStateReady) {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();
        }

        return $model;
    }

    public function setRelations(array $relations)
    {
        $model = parent::setRelations($relations);

        if ($this->fieldDefinitionStateReady) {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();
        }

        return $model;
    }

    public function setTableDisplayValue(string $slug, mixed $value): void
    {
        $this->ensureFieldDefinitionState();

        $this->tableDisplayCache[$slug] = $value;
    }

    public function syncChanges()
    {
        $this->ensureFieldDefinitionState();
        $this->quarantineInactiveProviderFieldState();

        parent::syncChanges();
        $this->quarantineInactiveProviderFieldState();

        return $this;
    }

    public function syncOriginal()
    {
        $this->mergeAttributesFromCachedCasts();

        if ($this->fieldDefinitionStateReady) {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();
        }

        $this->original = $this->attributes;

        return $this;
    }

    public function syncOriginalAttributes($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        $this->mergeAttributesFromCachedCasts();

        if ($this->fieldDefinitionStateReady) {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();
        }

        foreach ($attributes as $attribute) {
            if ($this->isInactiveProviderFieldSlug($attribute)) {
                if (array_key_exists($attribute, $this->quarantinedProviderFieldState['attributes'] ?? [])) {
                    $this->quarantinedProviderFieldState['original'][$attribute]
                        = $this->quarantinedProviderFieldState['attributes'][$attribute];
                } else {
                    unset($this->quarantinedProviderFieldState['original'][$attribute]);
                }

                continue;
            }

            $this->original[$attribute] = $this->attributes[$attribute];
        }

        return $this;
    }

    public function touch($attribute = null): bool
    {
        $this->assertNoInactiveProviderFieldPersistence(
            Arr::wrap($attribute),
            'touch',
        );

        return parent::touch($attribute);
    }

    public function unsetRelation($relation)
    {
        if ($relation === 'meta') {
            $this->normalizedMetaCache = [];
            $this->fieldsAttributeCache = null;
            unset($this->quarantinedProviderFieldState['metaRelation']);
        }

        if ($this->isInactiveProviderFieldSlug($relation)) {
            unset($this->quarantinedProviderFieldState['relations'][$relation]);
        }

        return parent::unsetRelation($relation);
    }

    public function unsetRelations()
    {
        unset(
            $this->quarantinedProviderFieldState['relations'],
            $this->quarantinedProviderFieldState['metaRelation'],
        );

        return parent::unsetRelations();
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

    public function wasPhysicalFieldPacked(string $slug): bool
    {
        return isset($this->packedPhysicalFieldValues[$slug]);
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

    protected function clearDefinitionDerivedInstanceCaches(): void
    {
        $this->fieldsAttributeCache = null;
        $this->normalizedMetaCache = [];
        $this->tableDisplayCache = [];
        $this->buildingFieldsAttribute = false;
    }

    protected function ensureFieldDefinitionState(): void
    {
        if (! $this->fieldDefinitionStateReady || $this->synchronizingFieldDefinitionState) {
            return;
        }

        if ($this->fieldDefinitionStateKey === FieldProviderRegistry::DECLARATIVE_CACHE_KEY
            && $this->fieldDefinitionGeneration === FieldCacheManager::generation()) {
            return;
        }

        $this->fieldDefinitionResolution();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function incrementOrDecrement($column, $amount, $extra, $method): int|false
    {
        $this->assertNoInactiveProviderFieldPersistence(
            [$column, ...array_keys($extra)],
            $method,
        );

        return $this->withoutInactiveAutomaticTimestamps(
            fn (): int|false => parent::incrementOrDecrement($column, $amount, $extra, $method),
            [$this->getUpdatedAtColumn()],
        );
    }

    /**
     * @param  array<string, float|int>  $columns
     * @param  array<string, mixed>  $extra
     */
    protected function incrementOrDecrementEach(array $columns, array $extra, string $method): int|false
    {
        $this->assertNoInactiveProviderFieldPersistence(
            [...array_keys($columns), ...array_keys($extra)],
            $method,
        );

        return $this->withoutInactiveAutomaticTimestamps(
            fn (): int|false => parent::incrementOrDecrementEach($columns, $extra, $method),
            [$this->getUpdatedAtColumn()],
        );
    }

    /**
     * Refresh per-instance state when a provider context/version or the global
     * field-cache generation changes. The resolution is committed only after
     * its parsed input slugs build successfully.
     */
    protected function synchronizeFieldDefinitionState(FieldProviderResolution $resolution): void
    {
        if (! $this->fieldDefinitionStateReady || $this->synchronizingFieldDefinitionState) {
            return;
        }

        $generation = FieldCacheManager::generation();

        if ($this->fieldDefinitionStateKey === $resolution->cacheKey
            && $this->fieldDefinitionGeneration === $generation) {
            return;
        }

        $this->synchronizingFieldDefinitionState = true;

        try {
            $this->restoreQuarantinedProviderFieldState();

            $previousSlugs = $this->resolvedInputFieldSlugs;
            $preservedFillable = array_values(array_unique(array_merge(
                array_diff(parent::getFillable(), $previousSlugs),
                $this->baseFillable,
            )));

            $this->clearDefinitionDerivedInstanceCaches();

            $currentSlugs = array_values(array_filter(
                $this->inputFieldsSlugs(),
                static fn (mixed $slug): bool => is_string($slug) && $slug !== '',
            ));
            $this->managedProviderFieldSlugs = array_values(array_unique(array_merge(
                $this->managedProviderFieldSlugs,
                $resolution->managedFieldSlugs,
            )));
            $this->inactiveProviderFieldSlugs = array_values(array_diff(
                $this->managedProviderFieldSlugs,
                $currentSlugs,
            ));

            $this->quarantineInactiveProviderFieldState(overwriteExisting: true);

            $this->fillable($preservedFillable);
            $this->mergeFillable($currentSlugs);

            $this->resolvedInputFieldSlugs = $currentSlugs;
            $this->fieldDefinitionStateKey = $resolution->cacheKey;
            $this->fieldDefinitionGeneration = $generation;
        } finally {
            $this->synchronizingFieldDefinitionState = false;
        }
    }

    /**
     * @param  array<int, mixed>  $columns
     */
    private function assertNoInactiveProviderFieldPersistence(array $columns, string $operation): void
    {
        $this->ensureFieldDefinitionState();
        $this->quarantineInactiveProviderFieldState();

        foreach ($columns as $column) {
            if (! is_string($column)) {
                continue;
            }

            $inactiveSlug = $this->inactiveProviderFieldSlugForPersistenceColumn($column);

            if ($inactiveSlug === null && $column === 'fields' && $this->inactiveProviderFieldSlugs !== []) {
                $inactiveSlug = $this->inactiveProviderFieldSlugs[0];
            }

            if ($inactiveSlug !== null) {
                throw new LogicException("Cannot {$operation} inactive provider-managed field [{$inactiveSlug}].");
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function attributePayloadColumns(mixed $payload): array
    {
        if (! is_array($payload) || $payload === []) {
            return [];
        }

        if (array_is_list($payload) && collect($payload)->every(fn (mixed $row): bool => is_array($row))) {
            return array_values(array_unique(array_merge(...array_map(
                fn (array $row): array => array_keys($row),
                $payload,
            ))));
        }

        return array_keys($payload);
    }

    /**
     * @return array<string, array{present: bool, value?: mixed}>
     */
    private function captureFieldContainerState(): array
    {
        $snapshot = [];

        foreach (['attributes', 'original', 'changes', 'previous'] as $stateName) {
            $state = $this->{$stateName};
            $present = array_key_exists('fields', $state);
            $snapshot[$stateName] = ['present' => $present];

            if ($present) {
                $snapshot[$stateName]['value'] = $state['fields'];
            }
        }

        return $snapshot;
    }

    /**
     * @return array<int, string>
     */
    private function columnList(mixed $columns): array
    {
        if (! is_array($columns)) {
            return is_string($columns) ? [$columns] : [];
        }

        if (array_is_list($columns)) {
            return array_values(array_filter($columns, is_string(...)));
        }

        return array_keys($columns);
    }

    private function discardQuarantinedAttributeStateForRefresh(): void
    {
        foreach (['attributes', 'original', 'changes', 'previous', 'classCastCache', 'attributeCastCache'] as $stateName) {
            unset($this->quarantinedProviderFieldState[$stateName]);
        }

        foreach (['attributes', 'original', 'changes', 'previous'] as $stateName) {
            unset(
                $this->quarantinedProviderFieldState['nested'][$stateName],
                $this->quarantinedProviderFieldState['nestedTemplates'][$stateName],
            );
        }
    }

    /**
     * @param  array<int, mixed>  $parameters
     * @return array<int, mixed>
     */
    private function guardForwardedPersistenceCall(string $method, array $parameters): array
    {
        $columns = match ($method) {
            'insertUsing', 'insertOrIgnoreUsing' => $this->columnList($parameters[0] ?? []),
            'updateOrInsert' => [
                ...$this->attributePayloadColumns($parameters[0] ?? []),
                ...$this->attributePayloadColumns(
                    ($parameters[1] ?? []) instanceof Closure ? [] : ($parameters[1] ?? []),
                ),
            ],
            'upsert' => [
                ...$this->attributePayloadColumns($parameters[0] ?? []),
                ...$this->columnList($parameters[2] ?? []),
                ...$this->columnList($this->uniqueIds()),
            ],
            'insertOrIgnoreReturning' => [
                ...$this->attributePayloadColumns($parameters[0] ?? []),
                ...$this->columnList($parameters[1] ?? []),
            ],
            default => $this->attributePayloadColumns($parameters[0] ?? []),
        };

        $this->assertNoInactiveProviderFieldPersistence($columns, $method);

        if ($method === 'updateOrInsert' && ($parameters[1] ?? null) instanceof Closure) {
            $values = $parameters[1];
            $parameters[1] = function (bool $exists) use ($method, $values): array {
                $resolvedValues = $values($exists);
                $this->assertNoInactiveProviderFieldPersistence(
                    $this->attributePayloadColumns($resolvedValues),
                    $method,
                );

                return $resolvedValues;
            };
        }

        return $parameters;
    }

    private function inactiveProviderFieldSlugForPersistenceColumn(string $column): ?string
    {
        if ($this->isInactiveProviderFieldSlug($column)) {
            return $column;
        }

        $baseColumn = Str::before($column, '->');

        if ($baseColumn !== $column && $this->isInactiveProviderFieldSlug($baseColumn)) {
            return $baseColumn;
        }

        if (! Str::startsWith($column, ['fields->', 'fields.'])) {
            return null;
        }

        $slug = Str::of($column)
            ->after('fields')
            ->ltrim('.->')
            ->replace('->', '.')
            ->replace(['"', "'"], '')
            ->toString();

        return $this->isInactiveProviderFieldSlug($slug) ? $slug : null;
    }

    private function isInactiveProviderFieldSlug(mixed $key): bool
    {
        return is_string($key) && in_array($key, $this->inactiveProviderFieldSlugs, true);
    }

    private function isInactiveProviderFieldStatePath(mixed $key): bool
    {
        if ($this->isInactiveProviderFieldSlug($key)) {
            return true;
        }

        return is_string($key)
            && Str::startsWith($key, 'fields.')
            && $this->isInactiveProviderFieldSlug(Str::after($key, 'fields.'));
    }

    private function persistWithoutInactiveProviderFieldState(Closure $persist): bool
    {
        $this->ensureFieldDefinitionState();
        $this->quarantineInactiveProviderFieldState();

        if ($this->inactiveProviderFieldSlugs === []) {
            return $persist();
        }

        $fieldContainerState = $this->captureFieldContainerState();

        try {
            return $persist();
        } finally {
            $this->restoreFieldContainerState($fieldContainerState);
            $this->quarantineInactiveProviderFieldState();
        }
    }

    private function providerMetaEntrySlug(mixed $entry): ?string
    {
        if ($entry instanceof Model) {
            $slug = $entry->getAttribute('key');
        } elseif (is_array($entry)) {
            $slug = $entry['key'] ?? null;
        } elseif (is_object($entry)) {
            $slug = $entry->key ?? null;
        } else {
            $slug = null;
        }

        return is_int($slug) || is_string($slug) ? (string) $slug : null;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $slugs
     */
    private function quarantineArrayEntries(
        array &$state,
        string $stateName,
        array $slugs,
        bool $overwriteExisting,
    ): void {
        foreach ($slugs as $slug) {
            if (! array_key_exists($slug, $state)) {
                continue;
            }

            if ($overwriteExisting
                || ! array_key_exists($slug, $this->quarantinedProviderFieldState[$stateName] ?? [])) {
                $this->quarantinedProviderFieldState[$stateName][$slug] = $state[$slug];
            }

            unset($state[$slug]);
        }
    }

    private function quarantineInactiveMetaRelation(bool $overwriteExisting): void
    {
        if (! array_key_exists('meta', $this->relations)) {
            return;
        }

        $relation = $this->relations['meta'];

        if ($relation instanceof Collection) {
            $filtered = $relation->reject(
                fn (mixed $entry): bool => $this->isInactiveProviderFieldSlug(
                    $this->providerMetaEntrySlug($entry),
                ),
            );
            $containsInactiveState = $filtered->count() !== $relation->count();
        } elseif (is_array($relation)) {
            $filtered = array_filter(
                $relation,
                fn (mixed $entry): bool => ! $this->isInactiveProviderFieldSlug(
                    $this->providerMetaEntrySlug($entry),
                ),
            );
            $containsInactiveState = count($filtered) !== count($relation);
        } else {
            $containsInactiveState = $this->isInactiveProviderFieldSlug(
                $this->providerMetaEntrySlug($relation),
            );
            $filtered = $containsInactiveState ? null : $relation;
        }

        if (! $containsInactiveState) {
            return;
        }

        if ($overwriteExisting || ! array_key_exists('metaRelation', $this->quarantinedProviderFieldState)) {
            $this->quarantinedProviderFieldState['metaRelation'] = $relation;
        }

        $this->relations['meta'] = $filtered;
        $this->normalizedMetaCache = [];
        $this->fieldsAttributeCache = null;
    }

    private function quarantineInactiveProviderFieldState(bool $overwriteExisting = false): void
    {
        if ($this->inactiveProviderFieldSlugs === []) {
            return;
        }

        foreach (['attributes', 'original', 'changes', 'previous'] as $stateName) {
            $this->quarantineArrayEntries(
                $this->{$stateName},
                $stateName,
                $this->inactiveProviderFieldSlugs,
                $overwriteExisting,
            );
            $this->quarantineNestedFieldEntries(
                $this->{$stateName},
                $stateName,
                $this->inactiveProviderFieldSlugs,
                $overwriteExisting,
            );
        }

        foreach (['relations', 'classCastCache', 'attributeCastCache', 'metaFields'] as $stateName) {
            $this->quarantineArrayEntries(
                $this->{$stateName},
                $stateName,
                $this->inactiveProviderFieldSlugs,
                $overwriteExisting,
            );
        }

        $this->quarantineInactiveMetaRelation($overwriteExisting);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $slugs
     */
    private function quarantineNestedFieldEntries(
        array &$state,
        string $stateName,
        array $slugs,
        bool $overwriteExisting,
    ): void {
        if (! isset($state['fields']) || ! is_array($state['fields'])) {
            return;
        }

        $fieldTemplate = $state['fields'];
        $capturedTemplate = false;

        foreach ($slugs as $slug) {
            $entry = [];

            if (array_key_exists($slug, $state['fields'])) {
                $entry['direct'] = $state['fields'][$slug];
            }

            if (str_contains($slug, '.') && Arr::has($state['fields'], $slug)) {
                $entry['nested'] = Arr::get($state['fields'], $slug);
            }

            if ($entry === []) {
                continue;
            }

            if (! $capturedTemplate
                && ($overwriteExisting
                    || ! array_key_exists($stateName, $this->quarantinedProviderFieldState['nestedTemplates'] ?? []))) {
                $this->quarantinedProviderFieldState['nestedTemplates'][$stateName] = $fieldTemplate;
                $capturedTemplate = true;
            }

            if ($overwriteExisting
                || ! array_key_exists($slug, $this->quarantinedProviderFieldState['nested'][$stateName] ?? [])) {
                $this->quarantinedProviderFieldState['nested'][$stateName][$slug] = $entry;
            }

            unset($state['fields'][$slug]);
            Arr::forget($state['fields'], $slug);
        }
    }

    private function readAfterSynchronizingFieldDefinition(Closure $read): mixed
    {
        if ($this->readingAttributeState) {
            return $read();
        }

        $this->readingAttributeState = true;

        try {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();

            return $read();
        } finally {
            $this->readingAttributeState = false;
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
        if ($key === 'metaFields') {
            $this->ensureFieldDefinitionState();
            $this->quarantineInactiveProviderFieldState();

            return $this->metaFields;
        }

        return $this->readAfterSynchronizingFieldDefinition(
            fn (): mixed => $this->resolveSynchronizedDynamicAttribute($key),
        );
    }

    private function resolveSynchronizedDynamicAttribute($key)
    {
        // 1. Real Eloquent state: getAttribute resolves a declared attribute,
        //    an accessor, or a loaded/lazy relation for this key.
        $value = $this->getAttribute($key);

        // 2. Any non-null result from (1) wins as-is — including falsy
        //    0/''/false; only a genuinely absent (null) attribute falls
        //    through to the relation/field resolution below. Aura field
        //    hydration is applied by resolveFieldValue(), leaving Eloquent's
        //    direct attribute/accessor contract unchanged.
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

    /**
     * @param  array<string, array{present: bool, value?: mixed}>  $snapshot
     */
    private function restoreFieldContainerState(array $snapshot): void
    {
        foreach ($snapshot as $stateName => $fieldState) {
            if ($fieldState['present']) {
                $this->{$stateName}['fields'] = $fieldState['value'];
            } else {
                unset($this->{$stateName}['fields']);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function restoreNestedFieldEntries(array &$state, array $entries, mixed $template = null): void
    {
        $currentFields = isset($state['fields']) && is_array($state['fields'])
            ? $state['fields']
            : [];
        $fields = is_array($template)
            ? array_replace_recursive($template, $currentFields)
            : $currentFields;

        foreach ($entries as $slug => $entry) {
            if (array_key_exists('direct', $entry)) {
                $fields[$slug] = $entry['direct'];
            }

            if (array_key_exists('nested', $entry)) {
                data_set($fields, $slug, $entry['nested']);
            }
        }

        $state['fields'] = $fields;
    }

    private function restoreQuarantinedProviderFieldState(): void
    {
        foreach (['attributes', 'original', 'changes', 'previous', 'relations', 'classCastCache', 'attributeCastCache', 'metaFields'] as $stateName) {
            foreach ($this->quarantinedProviderFieldState[$stateName] ?? [] as $slug => $value) {
                $this->{$stateName}[$slug] = $value;
            }
        }

        foreach ($this->quarantinedProviderFieldState['nested'] ?? [] as $stateName => $entries) {
            $this->restoreNestedFieldEntries(
                $this->{$stateName},
                $entries,
                $this->quarantinedProviderFieldState['nestedTemplates'][$stateName] ?? null,
            );
        }

        if (array_key_exists('metaRelation', $this->quarantinedProviderFieldState)) {
            $this->relations['meta'] = $this->quarantinedProviderFieldState['metaRelation'];
            $this->normalizedMetaCache = [];
            $this->fieldsAttributeCache = null;
        }

        $this->quarantinedProviderFieldState = [];
    }

    private function synchronizeQuarantinedOriginalAfterRefresh(): void
    {
        if (isset($this->quarantinedProviderFieldState['attributes'])) {
            $this->quarantinedProviderFieldState['original']
                = $this->quarantinedProviderFieldState['attributes'];
        } else {
            unset($this->quarantinedProviderFieldState['original']);
        }

        if (isset($this->quarantinedProviderFieldState['nested']['attributes'])) {
            $this->quarantinedProviderFieldState['nested']['original']
                = $this->quarantinedProviderFieldState['nested']['attributes'];
        } else {
            unset($this->quarantinedProviderFieldState['nested']['original']);
        }

        if (isset($this->quarantinedProviderFieldState['nestedTemplates']['attributes'])) {
            $this->quarantinedProviderFieldState['nestedTemplates']['original']
                = $this->quarantinedProviderFieldState['nestedTemplates']['attributes'];
        } else {
            unset($this->quarantinedProviderFieldState['nestedTemplates']['original']);
        }
    }

    /**
     * @param  array<int, string|null>  $automaticTimestampColumns
     */
    private function withoutInactiveAutomaticTimestamps(
        Closure $persist,
        array $automaticTimestampColumns,
    ): mixed {
        $timestampColumns = array_filter($automaticTimestampColumns);

        if (! $this->usesTimestamps()
            || collect($timestampColumns)->doesntContain(
                fn (string $column): bool => $this->isInactiveProviderFieldSlug($column),
            )) {
            return $persist();
        }

        $usesTimestamps = $this->timestamps;
        $this->timestamps = false;

        try {
            return $persist();
        } finally {
            $this->timestamps = $usesTimestamps;
        }
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function withoutInactiveProviderFieldState(array $state): array
    {
        foreach ($this->inactiveProviderFieldSlugs as $slug) {
            unset($state[$slug]);

            if (isset($state['fields']) && is_array($state['fields'])) {
                unset($state['fields'][$slug]);
                Arr::forget($state['fields'], $slug);
            }
        }

        return $state;
    }

    private function withoutInactiveProviderFieldValue(mixed $key, mixed $value): mixed
    {
        if ($key !== 'fields' || ! is_array($value)) {
            return $value;
        }

        return $this->withoutInactiveProviderFieldState(['fields' => $value])['fields'];
    }
}

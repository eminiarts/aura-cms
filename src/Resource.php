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

    protected int $fieldDefinitionGeneration = -1;

    protected ?string $fieldDefinitionStateKey = null;

    protected bool $fieldDefinitionStateReady = false;

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

    /**
     * Per-instance cache of the normalized meta map (see getMeta()).
     *
     * @var Collection|null
     */
    protected $normalizedMetaCache;

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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

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
        $this->normalizedMetaCache = null;

        if ($this->usesMeta()) {
            $this->load('meta'); // This will refresh only the 'meta' relationship
        }

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
            if ($this->normalizedMetaCache === null) {
                $meta = $this->meta
                    ->pluck('value', 'key')
                    ->except($this->inactiveProviderFieldSlugs);

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

    /**
     * @return BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
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

    public function save(array $options = []): bool
    {
        return $this->persistWithoutInactiveProviderFieldState(
            fn (): bool => parent::save($options),
        );
    }

    public function saveOrIgnore(array $options = [], array|string|null $uniqueBy = null): bool
    {
        return $this->persistWithoutInactiveProviderFieldState(
            fn (): bool => parent::saveOrIgnore($options, $uniqueBy),
        );
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        parent::setRawAttributes($attributes, false);

        if ($sync) {
            $this->original = $this->attributes;
        }

        return $this;
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
        $this->ensureFieldDefinitionState();

        $this->tableDisplayCache[$slug] = $value;
    }

    public function syncChanges()
    {
        $this->ensureFieldDefinitionState();
        $changes = $this->captureArrayEntries($this->changes, $this->inactiveProviderFieldSlugs);
        $previous = $this->captureArrayEntries($this->previous, $this->inactiveProviderFieldSlugs);

        parent::syncChanges();

        $this->restoreArrayEntries($this->changes, $this->inactiveProviderFieldSlugs, $changes);
        $this->restoreArrayEntries($this->previous, $this->inactiveProviderFieldSlugs, $previous);

        return $this;
    }

    public function syncOriginal()
    {
        $this->mergeAttributesFromCachedCasts();
        $this->original = $this->attributes;

        return $this;
    }

    public function syncOriginalAttributes($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        $this->mergeAttributesFromCachedCasts();

        foreach ($attributes as $attribute) {
            $this->original[$attribute] = $this->attributes[$attribute];
        }

        return $this;
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

    protected function clearDefinitionDerivedInstanceCaches(): void
    {
        $this->fieldsAttributeCache = null;
        $this->normalizedMetaCache = null;
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
            $previousSlugs = $this->resolvedInputFieldSlugs;
            $preservedFillable = array_values(array_unique(array_merge(
                array_diff(parent::getFillable(), $previousSlugs),
                $this->baseFillable,
            )));

            $this->clearDefinitionDerivedInstanceCaches();

            $currentSlugs = $this->inputFieldsSlugs();
            $this->managedProviderFieldSlugs = array_values(array_unique(array_merge(
                $this->managedProviderFieldSlugs,
                $resolution->managedFieldSlugs,
            )));
            $this->inactiveProviderFieldSlugs = array_values(array_diff(
                $this->managedProviderFieldSlugs,
                $currentSlugs,
                $this->baseFillable,
            ));

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
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $slugs
     * @return array<string, mixed>
     */
    private function captureArrayEntries(array $state, array $slugs): array
    {
        return array_intersect_key($state, array_flip($slugs));
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array{containerExists: bool, entries: array<string, array<string, mixed>>}
     */
    private function captureNestedFieldEntries(array $slugs): array
    {
        $containerExists = array_key_exists('fields', $this->attributes)
            && is_array($this->attributes['fields']);
        $fields = $containerExists ? $this->attributes['fields'] : [];
        $entries = [];

        foreach ($slugs as $slug) {
            if (array_key_exists($slug, $fields)) {
                $entries[$slug]['direct'] = $fields[$slug];
            }

            if (str_contains($slug, '.') && Arr::has($fields, $slug)) {
                $entries[$slug]['nested'] = Arr::get($fields, $slug);
            }
        }

        return [
            'containerExists' => $containerExists,
            'entries' => $entries,
        ];
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<string, mixed>
     */
    private function capturePersistenceState(array $slugs): array
    {
        return [
            'attributes' => $this->captureArrayEntries($this->attributes, $slugs),
            'original' => $this->captureArrayEntries($this->original, $slugs),
            'changes' => $this->captureArrayEntries($this->changes, $slugs),
            'previous' => $this->captureArrayEntries($this->previous, $slugs),
            'relations' => $this->captureArrayEntries($this->relations, $slugs),
            'classCastCache' => $this->captureArrayEntries($this->classCastCache, $slugs),
            'attributeCastCache' => $this->captureArrayEntries($this->attributeCastCache, $slugs),
            'metaFields' => $this->captureArrayEntries($this->metaFields, $slugs),
            'nestedFields' => $this->captureNestedFieldEntries($slugs),
        ];
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

        if ($this->inactiveProviderFieldSlugs === []) {
            return $persist();
        }

        $this->mergeAttributesFromCachedCasts();
        $slugs = $this->inactiveProviderFieldSlugs;
        $snapshot = $this->capturePersistenceState($slugs);

        $this->suppressInactivePersistenceState($slugs);

        try {
            return $persist();
        } finally {
            $this->restorePersistenceState($slugs, $snapshot);
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

    /**
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $slugs
     * @param  array<string, mixed>  $entries
     */
    private function restoreArrayEntries(array &$state, array $slugs, array $entries): void
    {
        foreach ($slugs as $slug) {
            unset($state[$slug]);
        }

        foreach ($entries as $slug => $value) {
            $state[$slug] = $value;
        }
    }

    /**
     * @param  array<int, string>  $slugs
     * @param  array{containerExists: bool, entries: array<string, array<string, mixed>>}  $snapshot
     */
    private function restoreNestedFieldEntries(array $slugs, array $snapshot): void
    {
        $fields = isset($this->attributes['fields']) && is_array($this->attributes['fields'])
            ? $this->attributes['fields']
            : [];

        foreach ($slugs as $slug) {
            unset($fields[$slug]);
            Arr::forget($fields, $slug);
        }

        foreach ($snapshot['entries'] as $slug => $entry) {
            if (array_key_exists('direct', $entry)) {
                $fields[$slug] = $entry['direct'];
            }

            if (array_key_exists('nested', $entry)) {
                data_set($fields, $slug, $entry['nested']);
            }
        }

        if ($snapshot['containerExists'] || $fields !== []) {
            $this->attributes['fields'] = $fields;
        } else {
            unset($this->attributes['fields']);
        }
    }

    /**
     * @param  array<int, string>  $slugs
     * @param  array<string, mixed>  $snapshot
     */
    private function restorePersistenceState(array $slugs, array $snapshot): void
    {
        $this->restoreArrayEntries($this->attributes, $slugs, $snapshot['attributes']);
        $this->restoreArrayEntries($this->original, $slugs, $snapshot['original']);
        $this->restoreArrayEntries($this->changes, $slugs, $snapshot['changes']);
        $this->restoreArrayEntries($this->previous, $slugs, $snapshot['previous']);
        $this->restoreArrayEntries($this->relations, $slugs, $snapshot['relations']);
        $this->restoreArrayEntries($this->classCastCache, $slugs, $snapshot['classCastCache']);
        $this->restoreArrayEntries($this->attributeCastCache, $slugs, $snapshot['attributeCastCache']);
        $this->restoreArrayEntries($this->metaFields, $slugs, $snapshot['metaFields']);
        $this->restoreNestedFieldEntries($slugs, $snapshot['nestedFields']);
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function suppressInactivePersistenceState(array $slugs): void
    {
        foreach ($slugs as $slug) {
            unset(
                $this->attributes[$slug],
                $this->classCastCache[$slug],
                $this->attributeCastCache[$slug],
                $this->metaFields[$slug],
            );

            if (isset($this->attributes['fields']) && is_array($this->attributes['fields'])) {
                unset($this->attributes['fields'][$slug]);
                Arr::forget($this->attributes['fields'], $slug);
            }
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

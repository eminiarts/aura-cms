<?php

namespace Aura\Base;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\TableResource;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Table-capable resource foundation for models whose fields all live in
 * physical columns of their own table.
 *
 * Where Resource resolves a field slug through the posts/meta storage matrix,
 * BaseResource resolves it against the model's own attributes only — there is
 * no meta lookup. The three members below are the pieces the shared traits
 * (AuraResourceTableConfig::display(), InputFields::shouldDisplayField()) call
 * on their host class; Resource supplies meta-aware versions of the same
 * three.
 */
class BaseResource extends Model implements DefinesFields, TableResource
{
    use AuraModelConfig;
    use InputFields;
    use InteractsWithTable;

    protected $appends = ['fields'];

    /**
     * Computed field map consumed by the table/view layers, keyed by slug.
     *
     * Nested (dotted) and hidden slugs are excluded, matching the accessor on
     * Resource, so display() treats both host classes the same way.
     */
    public function getFieldsAttribute(): Collection
    {
        return collect($this->inputFieldsSlugs())
            ->reject(fn ($slug) => str_contains((string) $slug, '.') || in_array($slug, $this->hidden, true))
            ->mapWithKeys(fn ($slug) => [$slug => $this->resolveFieldValue($slug)])
            ->filter(fn ($value, $slug) => ConditionalLogic::shouldDisplayField($this, $this->fieldBySlug($slug)));
    }

    /**
     * BaseResource stores nothing in the meta table, so the meta map is always
     * empty. Kept because shouldDisplayField() calls it on every host class.
     *
     * @return array<string,mixed>|null
     */
    public function getMeta($key = null)
    {
        return $key === null ? [] : null;
    }

    /**
     * Resolve a single field's raw (pre-display) value from a physical column.
     *
     * Precedence mirrors Resource::resolveFieldValue() minus the meta rungs:
     * a relation field's own getter, then a get{Slug}Field() accessor, then the
     * field class's get(), then the raw attribute.
     *
     * @param  mixed  $meta  Unused; present to match the Resource signature.
     */
    public function resolveFieldValue(string $slug, $meta = null)
    {
        $field = $this->fieldBySlug($slug);
        $class = $this->fieldClassBySlug($slug);
        $value = array_key_exists($slug, $this->attributes) ? $this->getAttribute($slug) : null;

        if ($class && method_exists($class, 'isRelation') && $class->isRelation($field) && method_exists($class, 'get')) {
            return $class->get($class, $value, $field);
        }

        $accessor = 'get'.Str::studly($slug).'Field';

        if (method_exists($this, $accessor)) {
            return $this->{$accessor}($value);
        }

        if ($class && method_exists($class, 'get')) {
            return $class->get($class, $value, $field);
        }

        return $value;
    }
}

<?php

namespace Aura\Base\Traits;

use Aura\Base\Models\Meta;
use Illuminate\Support\Collection;

trait AuraModelConfig
{
    use Concerns\AuraQueriesMeta;
    use Concerns\AuraResourceActions;
    use Concerns\AuraResourceConfiguration;
    use Concerns\AuraResourceIdentity;
    use Concerns\AuraResourceNavigation;
    use Concerns\AuraResourceTableConfig;
    use Concerns\AuraResourceTaxonomy;
    use Concerns\AuraResourceUrls;
    use Concerns\AuraResourceViews;

    public static $customTable = false;

    public array $metaFields = [];

    public static bool $usesMeta = true;

    protected $baseFillable = [];

    public function display($key)
    {
        $field = $this->fieldBySlug($key);
        $isInputField = in_array($key, $this->inputFieldsSlugs(), true);

        // Fast path: a plain input field without conditional logic resolves to
        // exactly the same value it would have inside the full `fields`
        // collection, so resolve just this one field instead of building every
        // field value for every rendered cell.
        if ($isInputField && $this->canFastPathDisplay($key, $field)) {
            return $this->formatDisplayValue($key, $this->resolveFieldValue($key));
        }

        // Keys that are not input fields (id, title, raw attributes) are never
        // present in the `fields` collection, so building it is wasted work —
        // resolve the raw attribute directly.
        if (! $isInputField) {
            return $this->displayRawAttribute($key);
        }

        // Full accessor path: input fields with conditional logic (whose
        // closures may need the complete `fields` structure) and fields that the
        // accessor may filter out.
        $fields = $this->fields;

        if ($fields instanceof Collection) {
            $fields = $fields->toArray();
        }

        if (! is_array($fields)) {
            $fields = [];
        }

        if (array_key_exists($key, $fields)) {
            return $this->formatDisplayValue($key, $fields[$key]);
        }

        return $this->displayRawAttribute($key);
    }

    public function getBaseFillable()
    {
        return $this->baseFillable;
    }

    public function getMetaForeignKey()
    {
        return $this->meta()->getForeignKeyName();
    }

    public function getMetaTable()
    {
        return $this->meta()->getRelated()->getTable();

        // return (new Meta())->getTable();
    }

    /**
     * Determine whether a field is stored in the meta table.
     *
     * Storage combinations:
     * - posts table + meta: base fillable fields stay on posts; input fields outside base fillable use meta.
     * - posts table without meta: no fields use meta.
     * - custom table without meta: input field slugs are custom-table columns.
     * - custom table + meta: base fillable fields are custom-table columns; remaining input fields use meta.
     */
    public function isMetaField($key): bool
    {
        if ($key === 'id') {
            return false;
        }

        // If the model does not use meta, return false
        if (! $this->usesMeta()) {
            return false;
        }

        // If the key is in Base fillable, it is not a meta field
        if (in_array($key, $this->getBaseFillable(), true)) {
            return false;
        }

        // If the key is in the fields, it is a meta field
        if (in_array($key, $this->inputFieldsSlugs(), true)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether a field is stored directly on the model table.
     *
     * For posts-mode resources this means the base posts columns. For custom-table resources without meta,
     * every input field slug is a table column. For custom-table resources with meta, only base fillable
     * fields are table columns and remaining input fields are stored in meta.
     */
    public function isTableField($key): bool
    {
        if (in_array($key, $this->getBaseFillable(), true)) {
            return true;
        }

        if ($this->usesCustomTable() && ! $this->usesMeta()) {
            return in_array($key, $this->inputFieldsSlugs(), true);
        }

        return false;
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        if (! $this->usesMeta()) {
            return;
        }

        return $this->morphMany(Meta::class, 'metable');
    }

    public function saveMetaField(array $metaFields): void
    {
        $this->saveMetaFields($metaFields);
    }

    public function saveMetaFields(array $metaFields): void
    {
        $this->metaFields = array_merge($this->metaFields, $metaFields);
    }

    public function team()
    {
        return $this->belongsTo(config('aura.resources.team'));
    }

    public static function usesCustomTable(): bool
    {
        return static::$customTable;
    }

    public static function usesMeta(): bool
    {
        return static::$usesMeta;
    }

    /**
     * Decide whether display($key) can skip the full `fields` accessor and
     * resolve only the requested field. Callers must already have confirmed the
     * key is an input field slug.
     */
    protected function canFastPathDisplay(string $key, $field): bool
    {
        // No field definition: let the full path handle attribute fallback.
        if (! $field) {
            return false;
        }

        // Conditional logic may depend on the complete `fields` structure or on
        // other fields' resolved values, so keep those on the full path.
        if (! empty($field['conditional_logic'])) {
            return false;
        }

        // Hidden keys (e.g. 'meta') are filtered out of the accessor.
        if (in_array($key, $this->hidden, true)) {
            return false;
        }

        // Nested/dotted fields are filtered out of the accessor; keep their
        // (null) behavior on the full path.
        if (str_contains($key, '.')) {
            return false;
        }

        return true;
    }

    /**
     * Resolve a raw (non-input-field) attribute for display. Mirrors the
     * attribute-fallback branch of the previous display() implementation,
     * including HTML-escaping since table/view blades render the result raw.
     */
    protected function displayRawAttribute(string $key)
    {
        if (! isset($this->{$key})) {
            return;
        }

        $value = $this->{$key};

        // if $value is an array, implode it
        if (is_array($value)) {
            return implode(', ', $value);
        }

        // This branch bypasses the field's own display() (which escapes
        // scalar values), so escape here too — the value is rendered raw
        // via {!! !!} in the table/view blades.
        return is_scalar($value) ? e($value) : $value;
    }

    /**
     * Run a resolved field value through the field's display() and flatten any
     * array result the same way the table cell expects.
     */
    protected function formatDisplayValue(string $key, $rawValue)
    {
        $value = $this->displayFieldValue($key, $rawValue);

        // if $value is an array, implode it
        if (is_array($value)) {
            $formattedValues = array_map(function ($subArray) {
                if (is_array($subArray)) {
                    return '['.implode(', ', $subArray).']';
                }

                return $subArray;
            }, $value);

            return implode(', ', $formattedValues);
        }

        return $value;
    }
}

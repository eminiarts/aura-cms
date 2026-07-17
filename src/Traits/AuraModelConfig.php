<?php

namespace Aura\Base\Traits;

use Aura\Base\ConditionalLogic;
use Aura\Base\Models\Meta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait AuraModelConfig
{
    public array $actions = [];

    public array $bulkActions = [];

    public static $contextMenu = true;

    public static $createEnabled = true;

    public static $customTable = false;

    public static $editEnabled = true;

    public static $globalSearch = true;

    public static bool $indexViewEnabled = true;

    public array $metaFields = [];

    public static $pluralName = null;

    public static $showActionsAsButtons = false;

    public static $singularName = null;

    public static $taxonomy = false;

    public array $taxonomyFields = [];

    public static bool $usesMeta = true;

    public static $viewEnabled = true;

    public array $widgetSettings = [
        'default' => '30d',
        'options' => [
            '1d' => '1 Day',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '60d' => '60 Days',
            '90d' => '90 Days',
            '180d' => '180 Days',
            '365d' => '365 Days',
            'all' => 'All',
            'ytd' => 'Year to Date',
            'qtd' => 'Quarter to Date',
            'mtd' => 'Month to Date',
            'wtd' => 'Week to Date',
            'last-year' => 'Last Year',
            'last-month' => 'Last Month',
            'last-week' => 'Last Week',
            'custom' => 'Custom',
        ],
    ];

    protected $baseFillable = [];

    protected static $dropdown = false;

    protected static ?string $group = 'Resources';

    protected static ?string $icon = null;

    protected static ?string $name = null;

    protected static array $searchable = [];

    protected static bool $showInNavigation = true;

    protected static ?string $slug = null;

    protected static ?int $sort = 100;

    protected static bool $title = false;

    protected static string $type = 'Resource';

    public function allowedToPerformActions()
    {
        return false;
    }

    public function createUrl()
    {
        $name = 'aura.'.$this->getSlug().'.create';

        if (! Route::has($name)) {
            return;
        }

        return route($name);
    }

    public function createView()
    {
        return 'aura::livewire.resource.create';
    }

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

    public function editHeaderView()
    {
        return 'aura::livewire.resource.edit-header';
    }

    public function editUrl()
    {
        if (! $this->getType() || ! $this->id) {
            return;
        }

        $name = 'aura.'.$this->getSlug().'.edit';

        if (! Route::has($name)) {
            return;
        }

        return route($name, ['id' => $this->id]);
    }

    public function editView()
    {
        return 'aura::livewire.resource.edit';
    }

    public function getActions()
    {
        if (method_exists($this, 'actions')) {
            return $this->actions();
        }

        if (property_exists($this, 'actions')) {
            return $this->actions;
        }
    }

    public function getBadge() {}

    public function getBadgeColor() {}

    public function getBaseFillable()
    {
        return $this->baseFillable;
    }

    public function getBulkActions()
    {
        if (method_exists($this, 'bulkActions')) {
            return $this->bulkActions();
        }

        if (property_exists($this, 'bulkActions')) {
            return $this->bulkActions;
        }
    }

    public static function getContextMenu()
    {
        return static::$contextMenu;
    }

    public static function getDropdown()
    {
        return static::$dropdown;
    }

    public static function getFields()
    {
        return [];
    }

    public static function getGlobalSearch()
    {
        return static::$globalSearch;
    }

    public static function getGroup(): ?string
    {
        return static::$group;
    }

    public function getHeaders()
    {
        $fields = $this->indexFields();

        // Filter $fields based on Conditional Logic for roles
        $fields = $fields->filter(function ($field) {
            return ConditionalLogic::fieldIsVisibleTo($field, auth()->user());
        });

        $fields = $fields->pluck('name', 'slug')
            ->when($this->usesTitle(), function ($collection, $value) {
                return $collection->prepend('title', 'title');
            })
            ->prepend('ID', 'id');

        return $fields;
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public function getIndexRoute()
    {
        return route('aura.'.$this->getSlug().'.index');
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

    public static function getName(): ?string
    {
        return static::$name;
    }

    public static function getPluralName(): string
    {

        return static::$pluralName ?? str(static::$type)->plural();
    }

    public static function getShowInNavigation(): bool
    {
        return static::$showInNavigation;
    }

    public static function getSlug(): string
    {
        return static::$slug ?? Str::slug(static::$name);
    }

    public static function getSort(): ?int
    {
        return static::$sort;
    }

    public static function getType(): string
    {
        return static::$type;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function icon()
    {
        return $this->getIcon();
    }

    public function indexTableSettings()
    {
        return [];
    }

    public function indexUrl()
    {
        $name = 'aura.'.$this->getSlug().'.index';

        if (! Route::has($name)) {
            return;
        }

        return route($name);
    }

    public function indexView()
    {
        return 'aura::livewire.resource.index';
    }

    public function isAppResource()
    {
        return Str::startsWith(get_class($this), 'App');
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

    public function isNumberField($key)
    {
        if ($this->fieldBySlug($key)['type'] == 'Aura\\Base\\Fields\\Number') {
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

    public function isTaxonomy()
    {
        return static::$taxonomy;
    }

    public function isTaxonomyField($key)
    {
        // Check if the Field is a taxonomy 'type' => 'Aura\\Base\\Fields\\Tags',
        if (in_array($key, $this->inputFieldsSlugs())) {
            $field = $this->fieldBySlug($key);

            // Atm only tags, refactor later
            if (isset($field['type']) && $field['type'] == 'Aura\\Base\\Fields\\Tags') {
                return true;
            }
        }

        return false;
    }

    public function isVendorResource()
    {
        return ! $this->isAppResource();
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

    public function navigation()
    {
        return [
            'icon' => $this->icon(),
            'resource' => get_class($this),
            'type' => $this->getType(),
            'name' => $this->pluralName(),
            'slug' => $this->getSlug(),
            'sort' => $this->getSort(),
            'group' => $this->getGroup(),
            'route' => $this->getIndexRoute(),
            'dropdown' => $this->getDropdown(),
            'showInNavigation' => $this->getShowInNavigation(),
            'badge' => $this->getBadge(),
            'badgeColor' => $this->getBadgeColor(),
        ];
    }

    public function pluralName()
    {
        return __(static::$pluralName ?? Str::plural($this->singularName()));
    }

    public function rowView()
    {
        return 'aura::components.table.row';
    }

    public function saveMetaField(array $metaFields): void
    {
        $this->saveMetaFields($metaFields);
    }

    public function saveMetaFields(array $metaFields): void
    {
        $this->metaFields = array_merge($this->metaFields, $metaFields);
    }

    public function saveTaxonomyFields(array $taxonomyFields): void
    {
        $this->taxonomyFields = array_merge($this->taxonomyFields, $taxonomyFields);
    }

    public function scopeOrWhereMeta($query, ...$args)
    {
        if (count($args) === 3) {
            $key = $args[0];
            $operator = $args[1];
            $value = $args[2];

            return $query->orWhereHas('meta', function ($query) use ($key, $operator, $value) {
                $query->where('key', $key)->where('value', $operator, $value);
            });
        } elseif (count($args) === 2) {
            $key = $args[0];
            $value = $args[1];

            return $query->orWhereHas('meta', function ($query) use ($key, $value) {
                $query->where('key', $key)->where('value', $value);
            });
        } elseif (count($args) === 1 && is_array($args[0])) {
            $metaPairs = $args[0];

            return $query->orWhere(function ($query) use ($metaPairs) {
                foreach ($metaPairs as $key => $value) {
                    $query->whereHas('meta', function ($query) use ($key, $value) {
                        $query->where('key', $key)->where('value', $value);
                    });
                }
            });
        }

        return $query;
    }

    public function scopeWhereInMeta($query, $field, $values)
    {
        if ($values instanceof Collection) {
            $values = $values->toArray();
        }
        if (! is_array($values)) {
            $values = [$values];
        }

        return $query->whereHas('meta', function ($query) use ($field, $values) {
            $query->where('key', $field)->whereIn('value', $values);
        });
    }

    public function scopeWhereMeta($query, ...$args)
    {
        if (count($args) === 3) {
            $key = $args[0];
            $operator = $args[1];
            $value = $args[2];

            return $query->whereHas('meta', function ($query) use ($key, $operator, $value) {
                $query->where('key', $key)->where('value', $operator, $value);
            });
        } elseif (count($args) === 2) {
            $key = $args[0];
            $value = $args[1];

            return $query->whereHas('meta', function ($query) use ($key, $value) {
                $query->where('key', $key)->where('value', $value);
            });
        } elseif (count($args) === 1 && is_array($args[0])) {
            $metaPairs = $args[0];

            return $query->where(function ($query) use ($metaPairs) {
                foreach ($metaPairs as $key => $value) {
                    $query->whereHas('meta', function ($query) use ($key, $value) {
                        $query->where('key', $key)->where('value', $value);
                    });
                }
            });
        }

        return $query;
    }

    /**
     * Scope a query to only include models where meta contains a specific value.
     *
     * @param  Builder  $query
     * @param  string  $key
     * @param  mixed  $value
     * @return Builder
     */
    public function scopeWhereMetaContains($query, $key, $value)
    {
        return $query->whereHas('meta', function ($query) use ($key, $value) {
            // Qualify as meta.value: SQLite's json_each() also exposes a "value"
            // column, so an unqualified whereJsonContains('value', ...) is ambiguous
            // and never matches on the test driver.
            $column = $query->getModel()->getTable().'.value';

            $query->where('key', $key)
                ->where(function ($query) use ($column, $value) {
                    // Match either string or numeric JSON array elements (e.g. "1" and 1).
                    $query->whereJsonContains($column, (string) $value);

                    if (is_numeric($value)) {
                        $query->orWhereJsonContains($column, (int) $value);
                    }
                });
        });
    }

    public function scopeWhereNotInMeta($query, $field, $values)
    {
        if ($values instanceof Collection) {
            $values = $values->toArray();
        }
        if (! is_array($values)) {
            $values = [$values];
        }

        return $query->whereDoesntHave('meta', function ($query) use ($field, $values) {
            $query->where('key', $field)->whereIn('value', $values);
        });
    }

    public function singularName()
    {
        return static::$singularName ?? Str::title(static::$slug);
    }

    public function tableComponentView()
    {
        return 'aura::livewire.table';
    }

    public function team()
    {
        return $this->belongsTo(config('aura.resources.team'));
    }

    public function title()
    {
        if (optional($this)->id) {
            return __($this->getType())." (#{$this->id})";
        }
    }

    public static function usesCustomTable(): bool
    {
        return static::$customTable;
    }

    public static function usesMeta(): bool
    {
        return static::$usesMeta;
    }

    public static function usesTitle(): bool
    {
        return static::$title;
    }

    public function viewHeaderView()
    {
        return 'aura::livewire.resource.view-header';
    }

    public function viewUrl()
    {
        if (! $this->getType() || ! $this->id) {
            return;
        }

        $name = 'aura.'.$this->getSlug().'.view';

        if (! Route::has($name)) {
            return;
        }

        return route($name, ['id' => $this->id]);
    }

    public function viewView()
    {
        return 'aura::livewire.resource.view';
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

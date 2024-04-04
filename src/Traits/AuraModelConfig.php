<?php

namespace Aura\Base\Traits;

use Aura\Base\ConditionalLogic;
use Aura\Base\Models\Meta;
use Aura\Base\Resources\Team;
use Illuminate\Support\Str;

trait AuraModelConfig
{
    public array $actions = [];

    public array $bulkActions = [];

    public static $showActionsAsButtons = false;

    public static $contextMenu = true;

    public static $createEnabled = true;

    public static $customTable = false;

    public static $editEnabled = true;

    public static $globalSearch = true;

    public static bool $indexViewEnabled = true;

    public array $metaFields = [];

    public static $pluralName = null;

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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->baseFillable = $this->getFillable();

        // Merge fillable fields from fields
        $this->mergeFillable($this->inputFieldsSlugs());
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    // public function __get($key)
    // {
    //     // // Title is a special case, for now
    //     if ($key == 'title') {
    //         return $this->getAttributeValue($key);
    //     }

    //     // Does not work atm
    //     // if ($key == 'roles') {
    //     //     return;
    //     //     return $this->getRolesField();
    //     // }

    //     $value = parent::__get($key);

    //     if ($value) {
    //         return $value;
    //     }

    //     return $this->displayFieldValue($key, $value);
    // }

    public function createUrl()
    {
        return route('aura.resource.create', [$this->getType()]);
    }

    public function createView()
    {
        return 'aura::livewire.resource.create';
    }

    public function display($key)
    {
        if (array_key_exists($key, $this->fields->toArray())) {
            $value = $this->displayFieldValue($key, $this->fields[$key]);

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

        if (isset($this->{$key})) {
            $value = $this->{$key};

            // if $value is an array, implode it
            if (is_array($value)) {
                return implode(', ', $value);
            }

            return $value;
        }
    }

    public function editHeaderView()
    {
        return 'aura::livewire.resource.edit-header';
    }

    public function editUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.resource.edit', ['slug' => $this->getType(), 'id' => $this->id]);
        }
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

    public function getBadge()
    {

    }

    public function getBadgeColor()
    {

    }

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
        return route('aura.resource.index', $this->getSlug());
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
        return static::$slug;
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

    public function indexView()
    {
        return 'aura::livewire.resource.index';
    }

    public function isAppResource()
    {
        return Str::startsWith(get_class($this), 'App');
    }

    public function isMetaField($key)
    {
        // If field is a taxonomy, it is not a meta field
        if ($this->isTaxonomyField($key)) {
            return false;
        }

        // If the key is in Base fillable, it is not a meta field
        if (in_array($key, $this->getBaseFillable())) {
            return false;
        }

        // If the key is in the fields, it is a meta field
        if (in_array($key, $this->inputFieldsSlugs())) {
            return true;
        }
    }

    public function isNumberField($key)
    {
        if ($this->fieldBySlug($key)['type'] == 'Aura\\Base\\Fields\\Number') {
            return true;
        }

        return false;
    }

    // Is the Field in the table?
    public function isTableField($key)
    {
        if (in_array($key, $this->getBaseFillable())) {
            return true;
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

        return $this->hasMany(Meta::class, 'post_id');
        //->whereIn('key', $this->inputFieldsSlugs())
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

    public function scopeWhereInMeta($query, $field, $values)
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        return $query->whereHas('meta', function ($query) use ($field, $values) {
            $query->where('key', $field)->whereIn('value', $values);
        });
    }

    public function scopeWhereMeta($query, ...$args)
    {
        if (count($args) === 2) {
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

    public function singularName()
    {
        return static::$singularName ?? Str::title(static::$slug);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
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

    public static function usesMeta(): string
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
        if ($this->getType() && $this->id) {
            return route('aura.resource.view', ['slug' => $this->getType(), 'id' => $this->id]);
        }
    }

    public function viewView()
    {
        return 'aura::livewire.resource.view';
    }
}

<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Models\Meta;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait AuraModelConfig
{
    public array $actions = [];

    public array $bulkActions = [];

    public static $customTable = false;

    public static $globalSearch = true;

    public array $metaFields = [];

    public static $pluralName = null;

    public static $singularName = null;

    public static $taxonomy = false;

    public array $taxonomyFields = [];

    public static bool $usesMeta = true;

    public static $contextMenu = true;

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

    protected static ?int $sort = 1000;

    protected static bool $title = false;

    protected static string $type = 'Resource';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->baseFillable = $this->getFillable();

        // Merge fillable fields from fields
        $this->mergeFillable($this->getFieldSlugs()->toArray());
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        // // Title is a special case, for now
        if ($key == 'title') {
            return $this->getAttributeValue($key);
        }

        // Does not work atm
        // if ($key == 'roles') {
        //     return;
        //     return $this->getRolesField();
        // }

        $value = parent::__get($key);

        if ($value) {
            return $value;
        }

        // ray()->count();
        return $this->displayFieldValue($key, $value);
    }

    public function createUrl()
    {
        return route('aura.post.create', [$this->getType()]);
    }

    public function display($key)
    {
        if (array_key_exists($key, $this->fields->toArray())) {
            $value = $this->displayFieldValue($key, $this->fields[$key]);

            // if $value is an array, implode it
            if (is_array($value)) {
                return implode(', ', $value);
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

    public function editUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.post.edit', ['slug' => $this->getType(), 'id' => $this->id]);
        }
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function getBaseFillable()
    {
        return $this->baseFillable;
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

    public static function getName(): ?string
    {
        return static::$name;
    }

    // public static function getNextId()
    // {
    //     $model = new static();

    //     $query = "show table status like '".$model->getTable()."'";

    //     $statement = DB::select($query);

    //     return $statement[0]->Auto_increment;
    // }

    public static function getPluralName(): string
    {

        return static::$pluralName ?? str(static::$type)->plural();
    }

    public static function getSearchable(): array
    {
        return static::$searchable;
    }

    public static function getShowInNavigation(): bool
    {
        return static::$showInNavigation;
    }

    public static function getSlug(): string
    {
        return static::$slug;
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
        if (in_array($key, $this->getAccessibleFieldKeys())) {
            return true;
        }
    }

    public function isNumberField($key)
    {
        if ($this->fieldBySlug($key)['type'] == 'Eminiarts\\Aura\\Fields\\Number') {
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
        // Check if the Field is a taxonomy 'type' => 'Eminiarts\\Aura\\Fields\\Tags',
        if (in_array($key, $this->getAccessibleFieldKeys())) {
            $field = $this->fieldBySlug($key);

            // Atm only tags, refactor later
            if (isset($field['type']) && $field['type'] == 'Eminiarts\\Aura\\Fields\\Tags') {
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
        return $this->hasMany(Meta::class, 'post_id')
        //->whereIn('key', $this->getAccessibleFieldKeys())
        ;
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
        ];
    }

    public function pluralName()
    {
        return __(static::$pluralName ?? Str::plural($this->singularName()));
    }

    public function getIndexRoute()
    {
        return route('aura.post.index', $this->getSlug());
    }

    public function rowView()
    {
        return 'aura::components.table.row';
    }

    public function tableView()
    {
        return 'aura::components.table.table';
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

    public function singularName()
    {
        return __(static::$singularName ?? Str::title(static::$slug));
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

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // public static function create($fields)
    // {
    //     $model = new static();

    //     $model->save();

    //     $model->update($fields);

    //     return $model;
    // }

    public function title()
    {
        if(optional($this)->id) {
            return $this->getType()." (#{$this->id})";
        }
    }

    public static function usesCustomTable(): bool
    {
        // dd('usesCustomTable', static::$customTable, static::$taxonomy);
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

    public function viewUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.post.view', ['slug' => $this->getType(), 'id' => $this->id]);
        }
    }

    public static function getSort(): ?int
    {
        return static::$sort;
    }

    public static function getContextMenu()
    {
        return static::$contextMenu;
    }
}

<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Models\Meta;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait AuraModelConfig
{
    public array $bulkActions = [];

    public array $metaFields = [];

    public static $pluralName = null;

    public static $singularName = null;

    public array $terms = [];

    public static bool $usesMeta = true;

    protected $baseFillable = [];

    protected static bool $customTable = false;

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
        // Title is a special case, for now
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

        return $this->displayFieldValue($key, $value);
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

    public function getFieldsAttribute()
    {
        $meta = $this->meta->pluck('value', 'key');

        $defaultValues = $this->inputFields()->pluck('slug')->mapWithKeys(fn ($value, $key) => [$value => null])->map(fn ($value, $key) => $meta[$key] ?? $value)->map(function ($value, $key) {
            // If the value is set on the model, use it
            if (isset($this->attributes[$key])) {
                return $this->attributes[$key];
            }
        });

        $meta = $meta->map(function ($meta, $key) {
            $class = $this->fieldClassBySlug($key);

            if ($class && method_exists($class, 'get')) {
                return $class->get($class, $meta);
            }

            return $meta;
        });

        return $defaultValues->merge($meta);
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

    public static function getNextId()
    {
        $model = new static();

        $query = "show table status like '".$model->getTable()."'";

        $statement = DB::select($query);

        return $statement[0]->Auto_increment;
    }

    public static function getPluralName(): string
    {
        return str(static::$type)->plural();
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
        return $this->hasMany(Meta::class, 'post_id')->whereIn('key', $this->getAccessibleFieldKeys());
    }

    public function navigation()
    {
        return [
            'icon' => $this->icon(),
            'type' => $this->getType(),
            'name' => $this->getName() ?? str($this->getType())->plural(),
            'slug' => $this->getSlug(),
            'sort' => $this->getSort(),
            'group' => $this->getGroup(),
            'dropdown' => $this->getDropdown(),
            'showInNavigation' => $this->getShowInNavigation(),
        ];
    }

    public function pluralName(): string
    {
        return static::$pluralName ?? Str::plural($this->singularName());
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

    public function saveTerms(array $terms): void
    {
        $this->terms = array_merge($this->terms, $terms);
    }

    public function singularName(): string
    {
        return static::$singularName ?? Str::title(static::$slug);
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
        return $this->getType()." (#{$this->id})";
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

    protected static function getSort(): ?int
    {
        return static::$sort;
    }
}

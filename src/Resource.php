<?php

namespace Aura\Base;

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
use Aura\Flows\Jobs\TriggerFlowOnCreatePostEvent;
use Aura\Flows\Jobs\TriggerFlowOnDeletedPostEvent;
use Aura\Flows\Jobs\TriggerFlowOnUpdatePostEvent;
use Aura\Flows\Resources\Flow;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Resource extends Model
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

    protected $fillable = ['title', 'content', 'type', 'status', 'fields', 'slug', 'user_id', 'parent_id', 'order', 'team_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['meta'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    protected $with = ['meta'];

    public function __call($method, $parameters)
    {
        if ($this->getFieldSlugs()->contains($method)) {

            $fieldClass = $this->fieldClassBySlug($method);

            if ($fieldClass->isRelation()) {
                $field = $this->fieldBySlug($method);

                return $fieldClass->relationship($this, $field);
            }
        }

        // ray($method);

        // Default behavior for methods not handled dynamically
        return parent::__call($method, $parameters);
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        if ($value) {
            return $value;
        }

        // If the key is in the fields array, then we want to return that
        if (is_null($value) && isset($this->fields[$key])) {
            return $this->fields[$key];
        }

        if ($this->getFieldSlugs()->contains($key)) {
            $fieldClass = $this->fieldClassBySlug($key);
            // $groupedField = $this->groupedFieldBySlug($key);

            if ($fieldClass->isRelation()) {
                $field = $this->fieldBySlug($key);

                return $fieldClass->get($this, $field);
            }
        }

        return $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachment()
    {
        return $this->hasMany(self::class, 'post_parent')
            ->where('post_type', 'attachment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(get_class($this), 'parent_id');
    }

    public function clearFieldsAttributeCache()
    {
        $this->fieldsAttributeCache = null;

        if ($this->usesMeta()) {
            $this->load('meta'); // This will refresh only the 'meta' relationship
        }

        // $this->refresh();
    }

    public function getBulkActions()
    {
        // get all flows with type "manual"

        // $flows = Flow::where('trigger', 'manual')
        //     ->where('options->resource', $this->type)
        //     ->get();

        // foreach ($flows as $flow) {
        //     $this->bulkActions['callManualFlow'] = $flow->name;
        // }

        return $this->bulkActions;
    }

    /**
     * @return string
     */
    public function getExcerptAttribute()
    {
        return $this->stripShortcodes($this->resource_excerpt);
    }

    public function getFieldsAttribute()
    {
        if (! isset($this->fieldsAttributeCache) || $this->fieldsAttributeCache === null) {
            $meta = $this->getMeta();

            $defaultValues = collect($this->inputFieldsSlugs())
                ->mapWithKeys(fn ($value, $key) => [$value => null])
                ->map(fn ($value, $key) => $meta[$key] ?? $value)
                ->map(function ($value, $key) {
                    // if the value is in $this->hidden, set it to null
                    if (in_array($key, $this->hidden)) {
                        // return null;
                    }

                    $class = $this->fieldClassBySlug($key);

                    if ($class && isset($this->{$key}) && method_exists($class, 'get')) {
                        return $class->get($class, $this->{$key});
                    }

                    // if $this->{$key} is set, then we want to use that
                    if (isset($this->{$key})) {
                        return $this->{$key};
                    }

                    if ($class && isset($this->attributes[$key]) && method_exists($class, 'get')) {
                        return $class->get($class, $this->attributes[$key]);
                    }

                    // if $this->attributes[$key] is set, then we want to use that
                    if (isset($this->attributes[$key])) {
                        return $this->attributes[$key];
                    }

                    // if there is a function get{Slug}Field on the model, use it
                    $method = 'get'.Str::studly($key).'Field';

                    if (method_exists($this, $method)) {
                        return $this->{$method}();
                    }

                    $class = $this->fieldClassBySlug($key);

                    if ($class && isset(optional($this)->{$key}) && method_exists($class, 'get')) {
                        return $class->get($class, $this->{$key} ?? null);
                    }

                    return $value;
                });

            $this->fieldsAttributeCache = $defaultValues->filter(function ($value, $key) {
                return $this->shouldDisplayField($this->fieldBySlug($key));
            });
        }

        return $this->fieldsAttributeCache;
    }

    // /**
    //  * Gets the featured image if any
    //  * Looks in meta the _thumbnail_id field.
    //  *
    //  * @return string
    //  */
    // public function getImageAttribute()
    // {
    //     if ($this->thumbnail and $this->thumbnail->attachment) {
    //         return $this->thumbnail->attachment->guid;
    //     }
    // }

    public function getMeta($key = null)
    {
        if ($this->usesCustomTable()) {
            return collect();
        }

        if ($this->usesMeta() && optional($this)->meta && ! is_string($this->meta)) {

            $meta = $this->meta->pluck('value', 'key');

            // Cast Attributes
            $meta = $meta->map(function ($meta, $key) {
                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $meta);
                }

                return $meta;
            });

            if ($key) {
                return $meta[$key] ?? null;
            }

            return $meta;
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

    // Override isRelation
    public function isRelation($key)
    {
        $modelMethods = get_class_methods($this);

        $possibleRelationMethods = [$key, Str::camel($key)];

        foreach ($possibleRelationMethods as $method) {

            if (in_array($method, $modelMethods) && ($this->{$method}() instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revision()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('post_type', 'revision');
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
            //$item['widget'] = app($item['type'])->widget($item);

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
            static::addGlobalScope(new TypeScope());
        }

        static::addGlobalScope(app(TeamScope::class));

        static::addGlobalScope(new ScopedScope());

        static::creating(function ($model) {
            // if (! $model->team_id) {
            //     $model->team_id = 1;
            // }
        });

        static::saved(function ($model) {
            $model->clearFieldsAttributeCache();
        });

        // static::created(function ($post) {
        //     dispatch(new TriggerFlowOnCreatePostEvent($post));
        // });

        // static::updated(function ($post) {
        //     dispatch(new TriggerFlowOnUpdatePostEvent($post));
        // });

        // static::deleted(function ($post) {
        //     dispatch(new TriggerFlowOnDeletedPostEvent($post));
        // });
    }
}

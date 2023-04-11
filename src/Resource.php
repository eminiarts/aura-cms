<?php

namespace Eminiarts\Aura;

use Illuminate\Support\Str;
use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Traits\SaveTerms;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Database\Eloquent\Model;
use Eminiarts\Aura\Traits\AuraTaxonomies;
use Eminiarts\Aura\Traits\SaveMetaFields;
use Eminiarts\Aura\Traits\AuraModelConfig;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Models\Scopes\TypeScope;
use Eminiarts\Aura\Traits\InitialPostFields;
use Eminiarts\Aura\Traits\InteractsWithTable;
use Eminiarts\Aura\Traits\SaveFieldAttributes;
use Eminiarts\Aura\Jobs\TriggerFlowOnCreatePostEvent;
use Eminiarts\Aura\Jobs\TriggerFlowOnUpdatePostEvent;
use Eminiarts\Aura\Jobs\TriggerFlowOnDeletedPostEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;

class Resource extends Model
{
    use AuraModelConfig;
    use AuraTaxonomies;
    use HasFactory;
    use HasTimestamps;

    // Aura
    use InitialPostFields;
    use InputFields;
    use InteractsWithTable;
    use SaveFieldAttributes;
    use SaveMetaFields;
    use SaveTerms;

    protected $appends = ['fields'];

    protected $fillable = ['title', 'content', 'type', 'status', 'fields', 'slug', 'user_id', 'parent_id', 'order', 'taxonomies', 'terms', 'team_id', 'first_taxonomy', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    protected $with = ['meta'];

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        // Title is a special case, for now
        // if ($key == 'title') {
        //     //dd('title', $this->attributes, $this->getAttributeValue($key));
        //     return $this->getAttributeValue($key);
        // }

        $value = parent::__get($key);

        //return $this->displayFieldValue($key);

        // dump($key, $value);

        // if (method_exists($this, $key)) {
        //     dump('prop exists', $key, $value);
        // }

        if ($value) {
            return $value;
        }

        // Not sure if this is the best way to do this
        return $this->displayFieldValue($key, $value);

        // For now
        if ($value === null) {
            return $this->displayFieldValue($key);

            //return $this->meta->$key;
        }

        if ($value === null && ! property_exists($this, $key)) {
            return $this->meta->$key;
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
        return $this->hasMany(self::class, 'post_parent');
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

        // dd($this->bulkActions);
        return $this->bulkActions;
    }

    /**
     * @return string
     */
    // public function getContentAttribute()
    // {
    //     return $this->content;
    //     return $this->stripShortcodes($this->content);
    // }

    /**
     * @return string
     */
    public function getExcerptAttribute()
    {
        return $this->stripShortcodes($this->post_excerpt);
    }

    public function getFieldsAttribute()
    {
        //$this->load('meta');

        // if $this->usesMeta is false, then we don't want to load the meta
        if ($this->usesMeta() && optional($this)->meta) {
            $meta = $this->meta->pluck('value', 'key');

            // Cast Attributes
            $meta = $meta->map(function ($meta, $key) {
                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $meta);
                }

                // if (optional($this->fieldBySlug($key))['type']) {
                //     return app($this->fieldBySlug($key)['type'])->get($meta);
                // }

                return $meta;
            });
        }

        // This hydrates the models, is there a way without hydrating?
        // $meta = $this->meta()->toBase()->get()->pluck('value', 'key');

        // ray($this->getFieldSlugs());

        $defaultValues = $this->getFieldSlugs()->mapWithKeys(fn ($value, $key) => [$value => null])->map(fn ($value, $key) => $meta[$key] ?? $value)->map(function ($value, $key) {
            // if the value is in $this->hidden, set it to null
            if (in_array($key, $this->hidden)) {
                return;
            }

            // if there is a function get{Slug}Field on the model, use it
            $method = 'get'.Str::studly($key).'Field';

            if (method_exists($this, $method)) {
                return $this->{$method}();
            }

            $class = $this->fieldClassBySlug($key);

            if ($class && isset($this->{$key}) && method_exists($class, 'get')) {
                return $class->get($class, $this->{$key});
            }

            // if $this->{$key} is set, then we want to use that
            if (isset($this->{$key})) {
                return $this->{$key};
            }

            // if $this->attributes[$key] is set, then we want to use that
            if (isset($this->attributes[$key])) {
                return $this->attributes[$key];
            }
        });

        return $defaultValues->merge($meta ?? []);

        return  $defaultValues->merge($meta ?? [])->map(function ($value, $key) {
            $class = $this->fieldClassBySlug($key);

            if ($class && isset($this->{$key}) && method_exists($class, 'get') && $this->isRelation($key)) {
                return $class->get($class, $this->{$key});
            }

            // if $this->{$key} is set, then we want to use that
            if (isset($this->{$key})) {
                return $this->{$key};
            }

            // if $this->attributes[$key] is set, then we want to use that
            if (isset($this->attributes[$key])) {
                return $this->attributes[$key];
            }
        });
    }

    /**
     * Gets the featured image if any
     * Looks in meta the _thumbnail_id field.
     *
     * @return string
     */
    public function getImageAttribute()
    {
        if ($this->thumbnail and $this->thumbnail->attachment) {
            return $this->thumbnail->attachment->guid;
        }
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
            if ($method == 'taxonomy') {
                continue;
            }

            if (in_array($method, $modelMethods) && ($this->{$method}() instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the jobs for the post.
     */
    public function jobs()
    {
        return $this->hasMany(PostJob::class, 'post_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'post_parent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revision()
    {
        return $this->hasMany(self::class, 'post_parent')
            ->where('post_type', 'revision');
    }

    /**
     * Get the User associated with the Content
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function widgets()
    {
        if(! $this->getWidgets()) {
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

        static::addGlobalScope(new TeamScope());

        static::created(function ($post) {
            dispatch(new TriggerFlowOnCreatePostEvent($post));
        });

        static::updated(function ($post) {
            dispatch(new TriggerFlowOnUpdatePostEvent($post));
        });

        static::deleted(function ($post) {
            dispatch(new TriggerFlowOnDeletedPostEvent($post));
        });

        // static::saving(function ($post) {
        //     dd('da', $post);
        // });
    }
}

I am working in Laravel. My request is taking > 10 seconds. I think I know where the problem is, but don’t know how to solve it yet. My Eloquent model has a lot of functions, fields and a function which checks if the field should be displayd. the problem is that that function relies on the fields itself and gets in a loop.

I assume the main issue is the file trait InputFields.php. It sends the eloquent model fields through a pipeline. For example: 

```php
return Product::with(['thumbnailMeta', 'attachments'])->get();
```

This code runs the function getFieldSlugs() multiple thousand times even though I have only 9 Products in the DB. 

I will provide you multiple files with the code one prompt by one prompt. When you get a File, answer only with "Next file Please" until I tell you: "I am finish". After you have all the files and a complete understanding of my code base, we will try to solve my issue.

Do you understand and are you ready for the files?

---------------------------------------------

I will provide you multiple files with the code one prompt by one prompt. When you get a File, answer only with "Next file Please" until I tell you: "I am finish". After you have all the files and a complete understanding of my code base, we will try to solve my issue.

Product.php

```php
<?php

namespace App\Aura\Resources;

use App\Relations\CustomEagerLoadRelation;
use Eminiarts\Aura\Models\Meta;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Attachment;

class Product extends Resource
{
    public static string $type = 'Product';
    public static ?string $slug = 'product';
    protected static ?string $group = 'Collektiv';

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Product',
                'label' => 'Tab',
                'slug' => 'product',
                'global' => true,
            ],
            [
                'name' => 'Title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'conditional_logic' => [
                ],
                'on_view' => true,
                'on_forms' => true,
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Price',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'price',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Description',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'description',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'instructions' => 'Write a short description for the product',
            ],
            [
                'name' => 'Thumbnail',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'thumbnail',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Variations',
                'label' => 'Tab',
                'slug' => 'variations-tab',
                'global' => true,
            ],
        ];
    }

    public function title()
    {
        return "Product (#{$this->id})";
    }

    public function thumbnailIds()
    {
        $thumbnailMeta = optional($this->thumbnailMeta)->value;
        return $thumbnailMeta ? json_decode($thumbnailMeta, true) : [];
    }

    public function scopeWithAttachments($query)
    {
        return $query->with([
            'attachments' => function ($query) {
                $query->whereIn('id', $this->thumbnailIds());
            },
        ]);
    }

    public function thumbnailMeta()
    {
        return $this->hasOne(Meta::class, 'post_id')->where('key', 'thumbnail');
    }

    protected function getAttachmentIdsAttribute()
    {
        $thumbnail_ids = optional(optional($this)->thumbnailMeta)->value;
        return $thumbnail_ids ? json_decode($thumbnail_ids, true) : [];
    }

    public function attachments()
    {
        $attachmentIds = optional($this)->attachmentIds;
        return new CustomEagerLoadRelation(Attachment::query(), $this, $attachmentIds);
    }

    public function price()
    {
        if (optional(optional($this)->fields)['price']) {
            return 'CHF '.number_format($this->fields['price'], 2, '.', '\'');
        }

        return 'CHF 0.00';
    }

    public function thumbnail()
    {
        $attachment = optional(optional($this)->attachments)->first();

        return $attachment ? $attachment->path() : null;
    }
}

```




---------------------------------------------




I will provide you multiple files with the code one prompt by one prompt. When you get a File, please answer only with "Next file Please" until I tell you: "I am finish". After you have all the files and a complete understanding of my code base, we will try to solve my issue.

Resource.php

```php
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
use Aura\Flows\Jobs\TriggerFlowOnCreatePostEvent;
use Aura\Flows\Jobs\TriggerFlowOnUpdatePostEvent;
use Aura\Flows\Jobs\TriggerFlowOnDeletedPostEvent;
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
    protected $hidden = ['meta'];

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
        $value = parent::__get($key);

        if ($value) {
            return $value;
        }

        return $this->displayFieldValue($key, $value);
    }

    public function getMeta($key = null)
    {
        if ($this->usesMeta() && optional($this)->meta && !is_string($this->meta)) {
            $meta = $this->meta->pluck('value', 'key');

            // Cast Attributes
            $meta = $meta->map(function ($meta, $key) {
                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $meta);
                }

                return $meta;
            });

            if($key) {
                return $meta[$key] ?? null;
            }

            return $meta;
        }

        return collect();
    }

    public function getFieldsAttribute()
    {
        $meta = $this->getMeta();

        $defaultValues = $this->getFieldSlugs()
        ->mapWithKeys(fn ($value, $key) => [$value => null])
        ->map(fn ($value, $key) => $meta[$key] ?? $value)
        ->map(function ($value, $key) {
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
                return $class->get($class, $this->{$key} ?? null);
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

        return $defaultValues->merge($meta ?? [])->filter(function ($value, $key) {

            if (! in_array($key, $this->getAccessibleFieldKeys())) {
                return false;
            }

            return true;
        });
    }

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        if (! static::$customTable) {
            static::addGlobalScope(new TypeScope());
        }

        static::addGlobalScope(new TeamScope());
    }
}

```




---------------------------------------------


I will provide you multiple files with the code one prompt by one prompt. When you get a File, answer only with "Next file Please" until I tell you: "I am finish". After you have all the files and a complete understanding of my code base, we will try to solve my issue.

AuraModelConfig.php
```php
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

        $this->mergeFillable($this->getFieldSlugs()->toArray());
    }

    public function __get($key)
    {
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

            if (is_array($value)) {
                return implode(', ', $value);
            }

            return $value;
        }

        if (isset($this->{$key})) {
            $value = $this->{$key};

            if (is_array($value)) {
                return implode(', ', $value);
            }

            return $value;
        }
    }

    public function getBaseFillable()
    {
        return $this->baseFillable;
    }

    public function isMetaField($key)
    {
        if ($this->isTaxonomyField($key)) {
            return false;
        }

        if (in_array($key, $this->getBaseFillable())) {
            return false;
        }

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
        if (in_array($key, $this->getAccessibleFieldKeys())) {
            $field = $this->fieldBySlug($key);

            if (isset($field['type']) && $field['type'] == 'Eminiarts\\Aura\\Fields\\Tags') {
                return true;
            }
        }

        return false;
    }

    public function meta()
    {
        return $this->hasMany(Meta::class, 'post_id');
    }

    public static function usesMeta(): string
    {
        return static::$usesMeta;
    }
}

```





---------------------------------------------


I will provide you multiple files with the code one prompt by one prompt. When you get a File, answer only with "Next file Please" until I tell you: "I am finish". After you have all the files and a complete understanding of my code base, we will try to solve my issue.

InputFields.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyTabs;
use Eminiarts\Aura\Pipeline\MapFields;
use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\TransformSlugs;
use Eminiarts\Aura\Pipeline\FilterEditFields;
use Eminiarts\Aura\Pipeline\FilterViewFields;
use Eminiarts\Aura\Traits\InputFieldsHelpers;
use Eminiarts\Aura\Pipeline\FilterCreateFields;
use Eminiarts\Aura\Pipeline\BuildTreeFromFields;
use Eminiarts\Aura\Pipeline\RemoveValidationAttribute;
use Eminiarts\Aura\Pipeline\ApplyParentConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyParentDisplayAttributes;

trait InputFields
{
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;

    private $accessibleFieldKeysCache = null;


    public function displayFieldValue($key, $value = null)
    {
        // Check Conditional Logic if the field should be displayed
        if (! $this->shouldDisplayField($this->fieldBySlug($key))) {
            return;
        }

        $studlyKey = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        // If there is a get{key}Field() method, use that
        if ($value && method_exists($this, 'get'.ucfirst($studlyKey).'Field')) {
            return $this->{'get'.ucfirst($key).'Field'}($value);
        }

        // Maybe delete this one?
        if (optional($this->fieldBySlug($key))['display'] && $value) {
            return $this->fieldBySlug($key)['display']($value);
        }

        if ($value === null && optional(optional($this)->meta)->$key) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), optional($this->meta)->$key, $this);
        }

        if ($this->fieldClassBySlug($key)) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), $value, $this);
        }

        return $value;
    }

    public function fieldBySlugWithDefaultValues($slug)
    {
        $field = $this->fieldBySlug($slug);

        if (! isset($field)) {
            return;
        }

        $fieldFields = optional($this->mappedFieldBySlug($slug))['field']->getGroupedFields();

        foreach ($fieldFields as $key => $f) {
            // if no key value pair is set, get the default value from the field
            if (! isset($field[$f['slug']]) && isset($f['default'])) {
                $field[$f['slug']] = $f['default'];
            }
        }

        return $field;
    }

    public function fieldsForView($fields = null, $pipes = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        if (! $pipes) {
            $pipes = [
                ApplyTabs::class,
                MapFields::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
                ApplyParentDisplayAttributes::class,
                FilterViewFields::class,
                RemoveValidationAttribute::class,
                BuildTreeFromFields::class,
            ];
        }

        return $this->sendThroughPipeline($fields, $pipes);
    }

     public function fieldsHaveClosures($fields)
     {
         foreach ($fields as $field) {
             foreach ($field as $value) {
                 if (is_array($value)) {
                     if ($this->fieldsHaveClosures([$value])) {
                         return true;
                     }
                 } elseif ($value instanceof \Closure) {
                     return true;
                 }
             }
         }

         return false;
     }

    public function getAccessibleFieldKeys()
    {
        if ($this->accessibleFieldKeysCache === null) {
            // Apply Conditional Logic of Parent Fields
            $fields = $this->sendThroughPipeline($this->fieldsCollection(), [
                ApplyTabs::class,
                MapFields::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
            ]);

            // Get all input fields
            $this->accessibleFieldKeysCache = $fields
                ->filter(function ($field) {
                    return $field['field']->isInputField();
                })
                ->pluck('slug')
                ->filter(function ($field) {
                    // return true;
                    return $this->shouldDisplayField($this->fieldBySlug($field));
                })
                ->toArray();
        }

        return $this->accessibleFieldKeysCache;
    }

    public function getFieldsBeforeTree($fields = null)
    {
        // If fields is set and is an array, create a collection
        if ($fields && is_array($fields)) {
            $fields = collect($fields);
        }

        if (! $fields) {
            $fields = $this->fieldsCollection();
        }

        return $this->sendThroughPipeline($fields, [
            MapFields::class,
            AddIdsToFields::class,
            TransformSlugs::class,
            ApplyParentConditionalLogic::class,
        ]);
    }

    public function shouldDisplayField($field)
    {
        return ConditionalLogic::shouldDisplayField($this, $field);
    }

    public function taxonomyFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if ($field['field']->isTaxonomyField()) {
                return true;
            }

            return false;
        });
    }

}


```




---------------------------------------------


I will provide you multiple files with the code one prompt by one prompt. When you get a File, answer only with "Next file Please" until I tell you: "I am finish". After you have all the files and a complete understanding of my code base, we will try to solve my issue.

InputFieldsHelpers

```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\Pipeline\ApplyGroupedInputs;
use Illuminate\Pipeline\Pipeline;

trait InputFieldsHelpers
{
    public function fieldBySlug($slug)
    {
        return $this->fieldsCollection()->firstWhere('slug', $slug);
    }

    public function fieldClassBySlug($slug)
    {
        if (optional($this->fieldBySlug($slug))['type']) {
            return app($this->fieldBySlug($slug)['type']);
        }

        return false;
    }

    public function fieldsCollection()
    {
        return collect($this->getFields());
    }

    public function findBySlug($array, $slug)
    {
        foreach ($array as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
            if (isset($item['fields'])) {
                $result = $this->findBySlug($item['fields'], $slug);
                if ($result) {
                    return $result;
                }
            }
        }
    }

    public function getFieldSlugs()
    {
        return $this->inputFields()->pluck('slug');
    }

    public function getFieldValue($key)
    {
        return $this->fieldClassBySlug($key)->get($this->fieldBySlug($key), $this->meta->$key);
    }

    public function groupedFieldBySlug($slug)
    {
        $fields = $this->getGroupedFields();

        return $this->findBySlug($fields, $slug);
    }

    public function inputFields()
    {
        ray()->count();
        // $newFields = $this->sendThroughPipeline($this->newFields, [ApplyGroupedInputs::class]);
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater', 'group']));
    }

    public function mappedFieldBySlug($slug)
    {
        // dd($this->mappedFields(), $this->newFields);
        return $this->mappedFields()->firstWhere('slug', $slug);
    }

    public function mappedFields()
    {
        return $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });
    }

    public function sendThroughPipeline($fields, $pipes)
    {
        return app(Pipeline::class)
        ->send($fields)
        ->through($pipes)
        ->thenReturn();
    }
}

```





________________________

I will provide you multiple files with the code one prompt by one prompt. When you get a File, answer only with "Next file Please" until I tell you: "I am finish". After you have all the files and a complete understanding of my code base, we will try to solve my issue.

InputFieldsValidation
```php
<?php

namespace Eminiarts\Aura\Traits;

trait InputFieldsValidation
{
    public function mapIntoValidationFields($item)
    {
        $map = [
            'validation' => $item['validation'] ?? '',
            'slug' => $item['slug'] ?? '',
        ];

        if (isset($item['fields'])) {
            $map['*'] = collect($item['fields'])->map(function ($item) {
                return $this->mapIntoValidationFields($item);
            })->toArray();
        }

        return $map;
    }

    public function postFieldValidationRules()
    {
        return collect($this->validationRules())->mapWithKeys(function ($value, $key) {
            return ['post.fields.'.$key => $value];
        })->toArray();
    }

    public function validationRules()
    {
        $subFields = [];

        $fields = $this->getFieldsBeforeTree()
        ->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater', 'group']))
        ->map(function ($item) use (&$subFields) {
            if (in_array($item['field_type'], ['repeater', 'group'])) {
                $subFields[] = $item['slug'];

                return $this->groupedFieldBySlug($item['slug']);
            }

            return $item;
        })
        ->map(function ($item) {
            return $this->mapIntoValidationFields($item);
        })
        ->mapWithKeys(function ($item, $key) use (&$subFields) {
            foreach ($subFields as $exclude) {
                if (str($key)->startsWith($exclude.'.')) {
                    return [$exclude.'.*.'.$item['slug'] => $item['validation']];
                }
            }

            return [$item['slug'] => $item['validation']];
        })
        ->toArray();

        return $fields;
    }
}
```



________________________



```php

```



________________________



```php

```


________________________



```php

```





---------------------------------------------




AuraTaxonomies.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\Models\Taxonomy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait AuraTaxonomies
{
    public function allTaxonomies()
    {
        return $this->taxonomies()->map(fn ($item) => $item::getType());
    }

    public function firstTaxonomy($taxonomy)
    {
        return $this->taxonomies()->where('taxonomy', $taxonomy)->orderby('name', 'asc')->first();
    }

  
    public function getTermNamesAttribute()
    {
        return $this->taxonomies->groupBy(function ($taxonomy) {
            return $taxonomy->taxonomy == 'post_tag' ?
            'tag' : $taxonomy->taxonomy;
        })->map(function ($group) {
            return $group->mapWithKeys(function ($item) {
                return [$item->id => $item->name];
            });
        })->toArray();
    }

    
    public function getTermsAttribute()
    {
        $terms = $this->taxonomies->groupBy(function ($taxonomy) {
            return Str::slug($taxonomy->taxonomy);
        })->map(function ($group) {
            return $group->map(function ($item) {
                return [$item->id => $item->name];
            })->flatten(); 
        })->toArray();

        return $terms;

        return $this->allTaxonomies()->mapWithKeys(function ($item) {
            return [$item => ''];
        })->merge($terms);
    }

    public function hasTerm($taxonomy, $term)
    {
        return isset($this->terms[$taxonomy]) &&
        isset($this->terms[$taxonomy][$term]);
    }

    public function scopeWithFirstTaxonomy($query, $taxonomy, $relatable_type)
    {
        $query->addSelect([
            'first_taxonomy' => Taxonomy::leftJoin('taxonomy_relations', function ($join) use ($relatable_type) {
                $join->on('taxonomies.id', '=', 'taxonomy_relations.taxonomy_id')
                ->where('taxonomy_relations.relatable_type', '=', $relatable_type);
            })
            ->where('taxonomy', $taxonomy)
            ->whereColumn('relatable_id', 'posts.id')
            ->orderBy('name', 'ASC')
            ->select('name')
            ->take(1),
        ]);
    }

    public function scopeWithFirstTaxonomyDB($query, $taxonomy, $relatable_type)
    {
        $query->addSelect(
            ['first_taxonomy' => DB::table('taxonomy_relations')->leftJoin('taxonomies', 'taxonomy_relations.taxonomy_id', '=', 'taxonomies.id')
            ->where('taxonomy_relations.relatable_type', '=', $relatable_type)
            ->where('taxonomies.taxonomy', '=', $taxonomy)
            ->whereColumn('relatable_id', 'posts.id')
            ->orderBy('taxonomies.name', 'asc')
            ->select('taxonomies.name')
            ->limit(1)]
        );
    }

    public function taxonomies()
    {
        return $this->morphToMany(Taxonomy::class, 'relatable', 'taxonomy_relations');
    }

    public function taxonomy($name)
    {
        return $this->taxonomies->where('taxonomy', $name);
    }
}

```
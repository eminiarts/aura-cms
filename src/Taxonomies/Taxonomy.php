<?php

namespace Eminiarts\Aura\Taxonomies;

use Eminiarts\Aura\Models\Scopes\TaxonomyScope;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Models\Taxonomy as ModelsTaxonomy;
use Eminiarts\Aura\Models\TaxonomyMeta;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Traits\CustomTable;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Taxonomy extends Resource
{
    use CustomTable;

    public static $hierarchical = false;

    public static $taxonomy = true;

    protected $fillable = ['name', 'slug', 'taxonomy', 'description', 'parent', 'count'];

    protected static ?string $name = null;

    protected $table = 'taxonomies';

    protected static string $type = 'Taxonomy';

    public function component()
    {
        return 'fields.taxonomy';
    }

    // public function display($key)
    // {
    //     if ($this->fields && array_key_exists($key, $this->fields->toArray())) {
    //         return $this->displayFieldValue($key, $this->fields[$key]);
    //     }

    //     return $this->{$key};
    // }

    public function editUrl()
    {
        return route('aura.taxonomy.edit', ['slug' => $this->getType(), 'id' => $this->id]);
    }

    public function createUrl()
    {
        return route('aura.taxonomy.create', [ $this->getType()]);
    }

    public static function getFields()
    {
        return [
            'name' => [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'name',
            ],

            'description' => [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'conditional_logic' => [
                ],
                'slug' => 'description',
            ],
            'slug' => [
                'name' => 'Slug',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'slug',
            ],
            'count' => [
                'name' => 'Count',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'conditional_logic' => [
                ],
                'slug' => 'count',
                'on_forms' => false,
            ],

        ];
    }

    // public function getHeaders()
    // {
    //     return $this->inputFields()
    //         ->pluck('name', 'slug')
    //         ->prepend('ID', 'id');
    // }

    public static function getName(): ?string
    {
        return static::$name;
    }

    public function getTitleAttribute()
    {
        return str(get_class($this))->afterLast('\\');
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        return $this->hasMany(TaxonomyMeta::class, 'taxonomy_id');
    }


    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new TaxonomyScope());
        static::addGlobalScope(new TeamScope());

        static::saving(function ($taxonomy) {
            if (! isset($taxonomy->description)) {
                $taxonomy->description = '';
            }

            if (! isset($taxonomy->slug) && $taxonomy->name) {
                $taxonomy->slug = str($taxonomy->name)->slug();
            }

            if (! isset($taxonomy->taxonomy)) {
                $taxonomy->taxonomy = (new \ReflectionClass(new static()))->getShortName();
            }

            if (! isset($taxonomy->parent)) {
                $taxonomy->parent = 0;
            }

            if (! isset($taxonomy->count)) {
                $taxonomy->count = 0;
            }

            if (! $taxonomy->team_id && auth()->user()) {
                $taxonomy->team_id = auth()->user()->current_team_id;
            }

            unset($taxonomy->user_id);
            unset($taxonomy->type);
        });
    }
}

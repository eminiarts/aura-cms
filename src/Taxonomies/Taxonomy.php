<?php

namespace Eminiarts\Aura\Taxonomies;

use App\Models\Scopes\TaxonomyScope;
use App\Models\Taxonomy as ModelsTaxonomy;
use App\Models\TaxonomyMeta;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taxonomy extends ModelsTaxonomy
{
    use HasFactory;
    use InputFields;

    public function rowView()
    {
        return 'components.table.row';
    }

    public static string $type = '';

    protected $table = 'taxonomies';

    public static $hierarchical = false;

    public function component()
    {
        return 'fields.taxonomy';
    }

    public function getTitleAttribute()
    {
        return str(get_class($this))->afterLast('\\');
    }

    public static function getType()
    {
        return static::$type;
    }

    public function editUrl()
    {
        return route('taxonomy.edit', ['slug' => $this->taxonomy, 'id' => $this->id]);
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

    protected $fillable = ['name', 'slug', 'taxonomy', 'description', 'parent', 'count'];

    public static function getFields()
    {
        return [
            'name' => [
                'name' => 'Name',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'name',
            ],

            'description' => [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'App\\Aura\\Fields\\Text',
                'conditional_logic' => [
                ],
                'slug' => 'description',
            ],
            'slug' => [
                'name' => 'Slug',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'slug',
            ],
            'count' => [
                'name' => 'Count',
                'type' => 'App\\Aura\\Fields\\Number',
                'conditional_logic' => [
                ],
                'slug' => 'count',
                'on_forms' => false,
            ],

        ];
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new TaxonomyScope());

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
        });
    }

    public function getHeaders()
    {
        return $this->inputFields()
            ->pluck('name', 'slug')
            ->prepend('ID', 'id');
    }
}

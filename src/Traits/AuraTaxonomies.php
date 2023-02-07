<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura;
use Eminiarts\Aura\Taxonomies\Taxonomy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait AuraTaxonomies
{
    public function allTaxonomies()
    {
        return $this->getTaxonomies()->map(fn ($item) => $item::getType());
    }

    public function firstTaxonomy($taxonomy)
    {
        return $this->taxonomies()->where('taxonomy', $taxonomy)->orderby('name', 'asc')->first();
    }

    public static function getTaxonomies()
    {
        // Get this model fields where the field class type is taxonomy

        return Aura::taxonomiesFor(static::getType());
    }

    /**
     * Gets all the terms arranged taxonomy => terms[].
     *
     * @return array
     */
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

    /**
     * Gets all the terms arranged taxonomy => terms[].
     *
     * @return array
     */
    public function getTermsAttribute()
    {
        $terms = $this->taxonomies->groupBy(function ($taxonomy) {
            return Str::slug($taxonomy->taxonomy);
        })->map(function ($group) {
            return $group->map(function ($item) {
                // dd($item->term, $item);
                // return $item->id;
                return [$item->id => $item->name];
            })->flatten(); //->flatten()->implode(',');
        })->toArray();

        return $terms;

        return $this->allTaxonomies()->mapWithKeys(function ($item) {
            return [$item => ''];
        })->merge($terms);
    }

    /**
     * Whether the post contains the term or not.
     *
     * @param  string  $taxonomy
     * @param  string  $term
     * @return bool
     */
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
        // return $this->belongsToMany(Taxonomy::class, 'taxonomy_relations', 'relatable_id');
    }

    public function taxonomy($name)
    {
        return $this->taxonomies->where('taxonomy', $name);
    }
}

<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\Aura;

trait SaveTerms
{
    protected static function bootSaveTerms()
    {
        static::saving(function ($post) {
            if (isset($post->attributes['terms'])) {
                // dd('saving', $post->attributes['terms']);

                $post->saveTaxonomyFields($post->attributes['terms']);

                unset($post->attributes['terms']);
            }
        });

        static::saved(function ($post) {
            // taxonomyFields
            if (optional($post)->taxonomyFields) {
                $values = [];


                foreach ($post->taxonomyFields as $key => $value) {
                    // if value is null, continue
                    if (! $value) {
                        continue;
                    }

                    $values[] = collect($value)
                    ->map(fn ($i) => trim($i))
                    ->map(function ($item) use ($key) {
                        return Aura::findTaxonomyBySlug($key)::firstOrCreate(['name' => $item])->id;
                    })->mapWithKeys(fn ($i, $k) => [$i => ['order' => $k]])->toArray();

                    continue;

                    // For Now
                    // Get the Correct Order
                    // $values[] = collect($value)->mapWithKeys(fn ($i, $k) => [$i => ['order' => $k]])->toArray();
                }

                $values = collect($values)->mapWithKeys(function ($a) {
                    return $a;
                });

                $post->taxonomies()->sync($values);

                // dd('saved', $values, $post->fresh()->taxonomies);

                unset($post->attributes['taxonomyFields']);
            }
        });
    }
}

<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\Aura;

trait SaveTerms
{
    protected static function bootSaveTerms()
    {
        static::saving(function ($post) {
            if (isset($post->attributes['terms'])) {
                $post->saveTerms($post->attributes['terms']);

                unset($post->attributes['terms']);
            }
        });

        static::saved(function ($post) {
            // Terms
            if (isset($post->terms)) {
                $values = [];

                foreach ($post->terms as $key => $value) {
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

                unset($post->attributes['terms']);
            }
        });
    }
}

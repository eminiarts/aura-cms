<?php

namespace Eminiarts\Aura\Aura\Traits;

trait SaveFieldAttributes
{
    /**
     * Set Fields Attributes
     *
     * Take Fields Attributes and Put all fields from getFieldSlugs() in the Fields Column
     *
     * @param    $post
     * @return void
     */
    protected static function bootSaveFieldAttributes()
    {
        static::saving(function ($post) {
            if (! optional($post->attributes)['fields']) {
                $post->attributes['fields'] = [];
            }

            $post->getFieldSlugs()->each(function ($slug) use ($post) {
                if (optional($post->attributes)[$slug]) {
                    $class = $post->fieldClassBySlug($slug);

                    // Do not continue if the Field is not found
                    if (! $class) {
                        return;
                    }

                    // Do not set password fields manually, since they would overwrite the hashed password
                    if ($class instanceof \App\Aura\Fields\Password) {
                        return;
                    }

                    if (! array_key_exists($slug, $post->attributes['fields'])) {
                        $post->attributes['fields'][$slug] = $post->attributes[$slug];
                    }
                }

                if ($slug == 'title') {
                    return;
                }

                // Dont Unset Field if it is in baseFillable
                if (in_array($slug, $post->baseFillable)) {
                    return;
                }

                // Unset fields from the attributes
                unset($post->attributes[$slug]);
            });
        });
    }
}

<?php

namespace Aura\Base\Traits;

trait SaveFieldAttributes
{
    /**
     * Set Fields Attributes
     *
     * Take Fields Attributes and Put all fields from getFieldSlugs() in the Fields Column
     *
     * @param  $post
     * @return void
     */
    protected static function bootSaveFieldAttributes()
    {
        static::saving(function ($post) {

            // ray('SaveFieldAttributes', $post->attributes)->blue();

            if ($post->name == 'Test Post 1') {
                // dd($post)->red();
            }

            if (! optional($post->attributes)['fields']) {
                $post->attributes['fields'] = [];
            }

            collect($post->inputFieldsSlugs())->each(function ($slug) use ($post) {

                // ray($slug, array_key_exists( $slug, $post->attributes))->blue();

                if (array_key_exists($slug, $post->attributes)) {

                    $class = $post->fieldClassBySlug($slug);

                    if($slug == 'password') {
                        // ray('Password Field', $class)->red();
                    }

                    // Do not continue if the Field is not found
                    if (! $class) {
                        return;
                    }

                    // Do not set password fields manually, since they would overwrite the hashed password
                    if ($class instanceof \Aura\Base\Fields\Password) {

                        // If the password is available in the $post->attributes, unset it
                        if (empty($post->attributes[$slug])) {
                            unset($post->attributes[$slug]);
                            ray('Unset Empty Password Field')->red();
                        }

                        return;
                    }

                    if ($class instanceof \Aura\Base\Fields\ID) {
                        return;
                    }

                    if (! array_key_exists($slug, $post->attributes['fields'])) {
                        $post->attributes['fields'][$slug] = $post->attributes[$slug];
                    }

                    // Set the field value into nested fields array if it contains a dot
                    if (strpos($slug, '.') !== false) {
                        self::setNestedFieldValue($post->attributes['fields'], $slug, $post->attributes[$slug]);
                        // Unset the attribute from the main attributes array
                        // unset($post->attributes[$slug]);
                        unset($post->attributes['fields'][$slug]);
                    } else {
                        // If no dot, set the attribute directly in fields
                        // $post->attributes['fields'][$slug] = $post->attributes[$slug];
                    }
                }

                if ($slug == 'title') {
                    return;
                }

                // Dont unset Field if it is in baseFillable
                if (in_array($slug, $post->baseFillable)) {
                    return;
                }

                // ray($post->usesCustomTable(), $post->usesCustomMeta());
                // Dont unset Field if it is uses customTable
                if ($post->usesCustomTable() && ! $post->usesCustomMeta()) {
                    return;
                }
                // if ($post->usesCustomTable() && $post->usesCustomMeta()) {
                //     return;
                // }

                // Unset fields from the attributes
                unset($post->attributes[$slug]);
            });

            // ray('saving', $post)->green();

        });
    }

    /**
     * Set a nested field value based on the slug with dots.
     *
     * @param  string  $slug
     * @param  mixed  $value
     * @return void
     */
    protected static function setNestedFieldValue(array &$fields, $slug, $value)
    {
        $keys = explode('.', $slug);
        $temp = &$fields;

        foreach ($keys as $key) {
            if (! isset($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }

        $temp = $value;
    }
}

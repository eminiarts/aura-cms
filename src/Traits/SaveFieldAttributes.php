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

            if (!optional($post->attributes)['fields']) {
                $post->attributes['fields'] = [];
            }

            $fieldSlugs = collect($post->inputFieldsSlugs());

            ray($post);

            $fieldSlugs->each(function ($slug) use ($post) {
                if (optional($post->attributes)[$slug]) {
                    $class = $post->fieldClassBySlug($slug);

                    // Do not continue if the Field is not found
                    if (!$class) {
                        return;
                    }

                    // Do not set password fields manually, since they would overwrite the hashed password
                    if ($class instanceof \Aura\Base\Fields\Password) {
                        return;
                    }

                    if ($class instanceof \Aura\Base\Fields\ID) {
                        return;
                    }

                    // Set the field value into nested fields array if it contains a dot
                    if (strpos($slug, '.') !== false) {
                        self::setNestedFieldValue($post->attributes['fields'], $slug, $post->attributes[$slug]);
                        // Unset the attribute from the main attributes array
                        // unset($post->attributes[$slug]);
                        ray($slug);
                        unset($post->attributes['fields'][$slug]);
                    } else {
                        // If no dot, set the attribute directly in fields
                        // $post->attributes['fields'][$slug] = $post->attributes[$slug];
                    }
                }

                if ($slug == 'title') {
                    return;
                }

                // Don't unset Field if it is in baseFillable
                if (in_array($slug, $post->baseFillable)) {
                    return;
                }

                // Dont unset Field if it is uses customTable
                if ($post->usesCustomTable() && ! $post->usesCustomMeta()) {
                    return;
                }
                unset($post->attributes[$slug]);
            });

            ray($post->attributes)->red();
        });
    }

    /**
     * Set a nested field value based on the slug with dots.
     *
     * @param array  $fields
     * @param string $slug
     * @param mixed  $value
     * @return void
     */
    protected static function setNestedFieldValue(array &$fields, $slug, $value)
    {
        $keys = explode('.', $slug);
        $temp = &$fields;

        foreach ($keys as $key) {
            if (!isset($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }

        $temp = $value;
    }
}

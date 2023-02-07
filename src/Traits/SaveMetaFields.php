<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Str;

trait SaveMetaFields
{
    protected static function bootSaveMetaFields()
    {
        static::saving(function ($post) {
            if (isset($post->attributes['fields'])) {
                // ray('fields in savemetafields', $post->attributes['fields']);

                foreach ($post->attributes['fields'] as $key => $value) {
                    $class = $post->fieldClassBySlug($key);

                    // Do not continue if the Field is not found
                    if (! $class) {
                        continue;
                    }

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post = $post->{$method}($value);

                        continue;
                    }

                    // If the $class is a Password Field and the value is null, continue
                    if ($class instanceof \Eminiarts\Aura\Fields\Password && is_null($value)) {
                        // If the password is available in the $post->attributes, unset it
                        if (isset($post->attributes[$key])) {
                            unset($post->attributes[$key]);
                        }

                        continue;
                    }

                    if (method_exists($class, 'set')) {
                        $value = $class->set($value);
                    }

                    // If the field exists in the $post->getBaseFillable(), it should be safed in the table instead of the meta table
                    if (in_array($key, $post->getBaseFillable())) {
                        $post->attributes[$key] = $value;

                        continue;
                    }

                    // Update or create the meta field
                    $post->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                }

                unset($post->attributes['fields']);
            }
        });
    }
}

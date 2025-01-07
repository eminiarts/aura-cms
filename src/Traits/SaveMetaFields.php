<?php

namespace Aura\Base\Traits;

use Aura\Base\Models\Meta;
use Illuminate\Support\Str;

trait SaveMetaFields
{
    protected static function bootSaveMetaFields()
    {

        static::saving(function ($post) {

            if ($post instanceof \Aura\Base\Resources\User) {
            }

            if (isset($post->attributes['fields'])) {

                // Dont save Meta Fields if it is uses customTable
                if ($post->usesCustomTable() && ! $post->usesMeta()) {
                    unset($post->attributes['fields']);

                    return;
                }

                foreach ($post->attributes['fields'] as $key => $value) {
                    $key = (string) $key;

                    $class = $post->fieldClassBySlug($key);

                    // Do not continue if the Field is not found
                    if (! $class) {
                        continue;
                    }

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post->saveMetaField([$key => $value]);

                        // $post = $post->{$method}($value);

                        continue;
                    }

                    $field = $post->fieldBySlug($key);

                    if (isset($field['set']) && $field['set'] instanceof \Closure) {
                        $value = call_user_func($field['set'], $post, $field, $value);
                    }

                    if (method_exists($class, 'set')) {
                        $value = $class->set($post, $field, $value);
                    }

                    if (method_exists($class, 'saving')) {
                        // Store the result back to $post
                        $modifiedPost = $class->saving($post, $field, $value);

                        if ($modifiedPost) {
                            $post = $modifiedPost;
                        }

                    }

                    // Check if further processing should be skipped
                    if (method_exists($class, 'shouldSkip') && $class->shouldSkip($post, $field)) {
                        continue;
                    }

                    if ($class instanceof \Aura\Base\Fields\ID) {
                        // $post->attributes[$key] = $value;

                        // unset($post->attributes['fields'][$key]);

                        continue;
                    }

                    // If the field exists in the $post->getBaseFillable(), it should be safed in the table instead of the meta table
                    if (in_array($key, $post->getBaseFillable())) {
                        $post->attributes[$key] = $value;

                        continue;
                    }

                    if (in_array($key, $post->getFillable())) {
                        // Save the meta field to the model, so it can be saved in the Meta table
                        $post->saveMetaField([$key => $value]);
                    }

                    // Save the meta field to the model, so it can be saved in the Meta table
                    // $post->saveMetaField([$key => $value]);
                }

                unset($post->attributes['fields']);

                $post->clearFieldsAttributeCache();
            }

        });

        static::saved(function ($post) {
            if (isset($post->metaFields)) {

                foreach ($post->metaFields as $key => $value) {

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post = $post->{$method}($value);

                        continue;
                    }

                    $field = $post->fieldBySlug($key);
                    $class = $post->fieldClassBySlug((string) $key);

                    if (method_exists($class, 'saved')) {
                        $value = $class->saved($post, $field, $value);

                        continue;
                    }

                    if ($post->usesMeta()) {
                        $post->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                    }

                }

                $post->fireModelEvent('metaSaved');
            }
        });
    }
}

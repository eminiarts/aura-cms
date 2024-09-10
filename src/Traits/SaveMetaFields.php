<?php

namespace Aura\Base\Traits;

use Aura\Base\Models\Meta;
use Illuminate\Support\Str;

trait SaveMetaFields
{
    protected static function bootSaveMetaFields()
    {

        static::saving(function ($post) {

            if ($post instanceof \App\Aura\Resources\Product) {
                // ray('SaveMetaFields', $post->attributes)->red();
            }


            if (isset($post->attributes['fields'])) {

                // Dont save Meta Fields if it is uses customTable
                if ($post->usesCustomTable() && ! $post->usesCustomMeta()) {

                    // ray('hier')->red();
                    unset($post->attributes['fields']);

                    return;
                }

                foreach ($post->attributes['fields'] as $key => $value) {

                    if ($post instanceof \App\Aura\Resources\Product && $key === 'additional_services') {
                        //ray('key value', $key, $value)->red();
                    }

                    $class = $post->fieldClassBySlug($key);

                    // ray($key, $value)->red();

                    // Do not continue if the Field is not found
                    if (! $class) {
                        continue;
                    }

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post->saveMetaField([$key => $value]);

                        //$post = $post->{$method}($value);

                        continue;
                    }

                    // If the $class is a Password Field and the value is null, continue
                    if ($class instanceof \Aura\Base\Fields\Password && ($value === null || $value === '')) {

                        // If the password is available in the $post->attributes, unset it
                        if (isset($post->attributes[$key])) {
                            unset($post->attributes[$key]);
                        }

                        continue;
                    }

                    $field = $post->fieldBySlug($key);

                    // ray('field from SaveMetaFields', $field);

                    if (isset($field['set']) && $field['set'] instanceof \Closure) {
                        // dd('here');
                        $value = call_user_func($field['set'], $post, $field, $value);
                    }

                    if (method_exists($class, 'set')) {
                        $value = $class->set($post, $field, $value);
                        // $value = $class->set($value, $field);
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

                    if ($post instanceof \App\Aura\Resources\Product && $key === 'additional_services') {
                        // ray('key value', $key, $value)->green();
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


            if ($post instanceof \App\Aura\Resources\Product) {
                // ray('end', $post)->blue();
            }
        });

        static::saved(function ($post) {
            if (isset($post->metaFields)) {

                foreach ($post->metaFields as $key => $value) {

                    // ray($key, $value)->red();

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post = $post->{$method}($value);

                        continue;
                    }

                    $field = $post->fieldBySlug($key);
                    $class = $post->fieldClassBySlug($key);

                    // if (isset($field['set']) && $field['set'] instanceof \Closure) {
                    //     // dd('here');
                    //     $value = call_user_func($field['set'], $post, $field, $value);
                    // }

                    if (method_exists($class, 'saved')) {
                        // ray('saved', $class);

                        $value = $class->saved($post, $field, $value);
                        // $value = $class->set($value, $field);

                        continue;
                    }

                    if ($post->usesMeta()) {
                        $post->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                    }

                }

                $post->fireModelEvent('metaSaved');

                // Reload relation
                // $post->load('meta');
            }
        });
    }
}

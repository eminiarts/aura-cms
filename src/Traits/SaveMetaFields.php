<?php

namespace Aura\Base\Traits;

use Aura\Base\Fields\ID;
use Aura\Base\Resources\User;
use Illuminate\Support\Str;

trait SaveMetaFields
{
    /**
     * Persist the queued meta fields after the row itself is saved.
     *
     * Invoked from the saved listener registered in
     * SaveFieldAttributes::bootSaveFieldAttributes().
     */
    protected static function persistMetaFieldsOnSaved($post): void
    {
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

                // Do not continue if the Field is not found
                if (! $class) {
                    if ($post->usesMeta()) {
                        $post->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                    }

                    continue;
                }

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
    }

    /**
     * Consume the packed `fields` array during saving: route each value to
     * its column, meta queue, or field-class handler, then remove `fields`
     * from the attributes so it never reaches the SQL insert/update.
     *
     * Invoked as the last saving step of the pipeline registered in
     * SaveFieldAttributes::bootSaveFieldAttributes(). Deliberately not a
     * trait boot method — as a separate listener its order against the
     * packer depended on trait boot order, which PHP 8.5 changed (issue #37).
     */
    protected static function persistMetaFieldsOnSaving($post): void
    {
        if ($post instanceof User) {
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

                // Allow resources/plugins to consume custom form payloads that are
                // not regular Aura fields, e.g. "translations".
                $method = 'set'.Str::studly($key).'Field';

                if (! $class && method_exists($post, $method)) {
                    $post->{$method}($value);

                    continue;
                }

                // Do not continue if the Field is not found
                if (! $class) {
                    continue;
                }

                // if there is a function set{Slug}Field on the model, use it
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

                if ($class instanceof ID) {
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
    }
}

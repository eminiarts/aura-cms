<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Facades\Hash;

class Password extends Field
{
    public $edit = 'aura::fields.password';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    protected $shouldSkip = false;

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }

    public function hydrate() {}

    public function saving($post, $field, $value)
    {
        $key = $field['slug'];

        // If value is empty (null or empty string), prevent password update entirely
        if (empty($value)) {
            // For User model, remove password from all possible locations
            if ($post instanceof \App\Models\User || $post instanceof \Aura\Base\Resources\User) {
                // Remove password from attributes if it exists
                if (isset($post->attributes[$key])) {
                    unset($post->attributes[$key]);
                }
                // Remove from fields if it exists
                if (isset($post->attributes['fields'][$key])) {
                    unset($post->attributes['fields'][$key]);
                }

                // Unset the password attribute from the model
                $post->offsetUnset($key);

                // Force the model to forget the password attribute
                // $post->syncOriginal();

                return $post;
            }
        }

        return $post;
    }

    public function set($post, $field, &$value)
    {
        if (empty($value)) {
            // Mark that we should skip this field
            $this->shouldSkip = true;

            // Set a special marker to indicate we want to remove this attribute
            return;
        }

        // Only hash if not already hashed
        if (! Hash::isHashed($value)) {
            return Hash::make($value);
        }

        return $value;
    }

    /**
     * Check if this field should be skipped
     *
     * @param  mixed  $post
     * @param  array  $field
     * @return bool
     */
    public function shouldSkip($post, $field)
    {
        if ($this->shouldSkip) {
            $this->shouldSkip = false;

            return true;
        }

        return false;
    }
}

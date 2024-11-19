<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function set($post, $field, &$value)
    {
        $key = $field['slug'];

        // If value is empty (null or empty string), prevent password update entirely
        if (empty($value)) {
            // For User model, remove password from both attributes and fields
            if ($post instanceof \App\Models\User || $post instanceof \Aura\Base\Resources\User) {
                // Remove password from all possible locations
                if (isset($post->attributes[$key])) {
                    unset($post->attributes[$key]);
                }
                if (isset($post->attributes['fields'][$key])) {
                    unset($post->attributes['fields'][$key]);
                }
                // if (isset($post->$key)) {
                //     unset($post->$key);
                // }

                ray('unset', $post)->orange();

                $this->shouldSkip = true;
                return null;
            }
        }

        // If value is not empty, hash it
        $hashedValue = Hash::make($value);
        
        // For User model, set the password attribute directly
        if ($post instanceof \App\Models\User || $post instanceof \Aura\Base\Resources\User) {
            $post->password = $hashedValue;
        }

        return $hashedValue;
    }

    /**
     * Check if this field should be skipped
     * 
     * @param mixed $post
     * @param array $field
     * @return bool
     */
    public function shouldSkip($post, $field)
    {
        ray('shouldSkip', $this->shouldSkip)->red();

        if ($this->shouldSkip) {
            $this->shouldSkip = false;
            return true;
        }

        return false;
    }
}

<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Password extends Field
{
    public $edit = 'aura::fields.password';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }

    public function hydrate() {}

    public function set($post, $field, &$value)
    {
        $key = $field['slug'];

        // If value is empty (null or empty string), preserve the existing password
        if (empty($value)) {
            // For User model
            if ($post instanceof \App\Models\User || $post instanceof \Aura\Base\Resources\User) {
                // Remove password from fields if it exists
                if (isset($post->attributes['fields'][$key])) {
                    unset($post->attributes['fields'][$key]);
                }
                
                // Skip password update by returning early
                return null;
            }

            // For other models, preserve the existing password in fields
            if (isset($post->fields[$key])) {
                return $post->fields[$key];
            }

            return null;
        }

        // If value is not empty, hash it
        $hashedValue = Hash::make($value);
        
        // For User model, we need to set the password attribute directly
        if ($post instanceof \App\Models\User || $post instanceof \Aura\Base\Resources\User) {
            $post->password = $hashedValue;
        }

        return $hashedValue;
    }
}

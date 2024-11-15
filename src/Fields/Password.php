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
        return array_merge(parent::getFields(), [
        ]);
    }

    // public function get($field, $value)
    // {
    // }

    // Initialize the field on a LiveWire component
    // Probably not needed anymore
    public function hydrate() {}

    public function set($post, $field, &$value)
    {
        $key = $field['slug'];

        // If the password field is not dirty, unset it
        if (! $post->isDirty($key)) {
            ray('unset', $key)->red();
            unset($post->attributes[$key]);
            unset($post->attributes['fields'][$key]);
            return; // Exit the method
        }

        // If value is not empty, hash it
        if ($value) {
            $value = Hash::make($value);
            ray($value)->blue();
        }

        return $value;
    }
}

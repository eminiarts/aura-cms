<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class Password extends Field
{
    public $edit = 'aura::fields.password';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    // public function get($field, $value)
    // {
    // }

    // Initialize the field on a LiveWire component
    // Probably not needed anymore
    public function hydrate() {
        return null;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }

    public function set($post, $field, $value)
    {

        
        if ($value) {

            // ray('set', $value)->red();
            // if starts with $2y$, ray
            if (Str::startsWith($value, '$2y$')) {
                // ray('starts with $2y$', $value)->blue();
                // ray()->backtrace()->blue();
            }
            // Hash the password
            return Hash::make($value);
        }

        return $value;
    }
}

<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Facades\Hash;

class Password extends Field
{
    public $component = 'aura::fields.password';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    // public function get($field, $value)
    // {
    // }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }

    // Initialize the field on a LiveWire component
    public function hydrate() {}

    public function set($value)
    {
        // dd('set', $value);

        if ($value) {
            // Hash the password
            return Hash::make($value);
        }

        return $value;
    }
}

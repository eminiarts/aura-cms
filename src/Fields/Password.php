<?php

namespace Eminiarts\Aura\Fields;

use Illuminate\Support\Facades\Hash;

class Password extends Field
{
    public string $component = 'fields.password';

    protected string $view = 'components.fields.password';

    public function get($field, $value)
    {
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }

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

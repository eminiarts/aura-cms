<?php

namespace Eminiarts\Aura\Fields;

use Eminiarts\Aura\Resources\Attachment;

class Image extends Field
{
    public $component = 'aura::fields.image';

    public $view = 'aura::fields.view-value';

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $url = Attachment::find($value)->path();

        return "<img src='{$url}' class='w-32 h-32 object-cover rounded-lg shadow-lg'>";
    }
}

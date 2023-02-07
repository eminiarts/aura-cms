<?php

namespace App\Aura\Fields;

use App\Aura\Resources\Attachment;

class File extends Field
{
    public string $component = 'fields.file';

    protected string $view = 'components.fields.file';

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}

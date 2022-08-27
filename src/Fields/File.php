<?php

namespace Eminiarts\Aura\Fields;

use App\Aura\Resources\Attachment;

class File extends Field
{
    protected string $view = 'components.fields.file';

    public string $component = 'fields.file';

    public function get($field, $value)
    {
        return Attachment::find($value);
    }
}

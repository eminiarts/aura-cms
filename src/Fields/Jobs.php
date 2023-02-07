<?php

namespace App\Aura\Fields;

class Jobs extends Field
{
    public string $component = 'fields.jobs';

    public string $type = 'job';

    protected string $view = 'components.fields.jobs';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}

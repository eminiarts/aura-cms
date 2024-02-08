<?php

namespace Aura\Base\Fields;

class Jobs extends Field
{
    public $component = 'aura::fields.jobs';

    public string $type = 'job';

    // public $view = 'components.fields.jobs';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}

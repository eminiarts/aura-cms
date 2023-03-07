<?php

namespace Eminiarts\Aura\Fields;

class Jobs extends Field
{
    public string $component = 'aura::fields.jobs';

    public string $type = 'job';

    // public $view = 'components.fields.jobs';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}

<?php

namespace Aura\Base\Fields;

class View extends Field
{
    public $edit = 'aura::fields.view';

    // public $view = 'components.fields.view';

    public string $type = 'view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'View',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'view-tab',
                'style' => [],
            ],
            [
                'name' => 'View',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view',
            ],
        ]);
    }
}

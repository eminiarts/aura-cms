<?php

namespace Eminiarts\Aura\Fields;

class View extends Field
{
    public string $component = 'aura::fields.view';

    // public $view = 'components.fields.view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'View',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'view-tab',
                'style' => [],
            ],
            [
                'name' => 'View',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'view',
            ],
        ]);
    }
}

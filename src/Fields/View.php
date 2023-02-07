<?php

namespace App\Aura\Fields;

class View extends Field
{
    public string $component = 'fields.view';

    protected string $view = 'components.fields.view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'View',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'view-tab',
                'style' => [],
            ],
            [
                'name' => 'View',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'view',
            ],
        ]);
    }
}

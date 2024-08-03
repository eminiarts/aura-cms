<?php

namespace Aura\Base\Fields;

class LivewireComponent extends Field
{
    public $edit = 'aura::fields.livewire-component';

    // public $view = 'components.fields.livewire-component';

    public string $type = 'livewire-component';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Component',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'component-tab',
                'style' => [],
            ],
            [
                'name' => 'Component',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'component',
            ],
        ]);
    }
}

<?php

namespace Eminiarts\Aura\Fields;

class LivewireComponent extends Field
{
    public $component = 'aura::fields.livewire-component';

    // public $view = 'components.fields.livewire-component';

    public string $type = 'livewire-component';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Component',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'component-tab',
                'style' => [],
            ],
            [
                'name' => 'Component',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'component',
            ],
        ]);
    }
}

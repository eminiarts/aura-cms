<?php

namespace Eminiarts\Aura\Fields;

class LivewireComponent extends Field
{
    public string $component = 'fields.livewire-component';

    protected string $view = 'components.fields.livewire-component';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Component',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'component-tab',
                'style' => [],
            ],
            [
                'name' => 'Component',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'component',
            ],
        ]);
    }
}

<?php

namespace Aura\Base\Fields;

use Aura\Base\Services\EmbeddedComponentResolver;
use Aura\Base\Services\EmbeddedComponentSurface;

class LivewireComponent extends Field
{
    public $edit = 'aura::fields.livewire-component';

    public $index = 'aura::fields.livewire-component-index';

    public string $type = 'livewire-component';

    public $view = 'aura::fields.livewire-component-view';

    public function getFields(): array
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Component',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'component-tab',
                'style' => [],
            ],
            [
                'name' => 'Legacy edit component alias',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'component',
            ],
            [
                'name' => 'Edit component alias',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'component_aliases.edit',
            ],
            [
                'name' => 'View component alias',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'component_aliases.view',
            ],
            [
                'name' => 'Index component alias',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'component_aliases.index',
            ],
            [
                'name' => 'Fallback component alias',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'component_aliases.fallback',
            ],
            [
                'name' => 'Parameter mapper',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Class implementing MapsEmbeddedComponentParameters.',
                'validation' => '',
                'slug' => 'parameter_mapper',
            ],
            [
                'name' => 'Owner resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Resource class used for create rendering when no model is available.',
                'validation' => '',
                'slug' => 'owner_resource',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public function rendersOnIndex(array $field = []): bool
    {
        return app(EmbeddedComponentResolver::class)->supportsSecureSurface(
            $field,
            EmbeddedComponentSurface::Index,
        );
    }
}

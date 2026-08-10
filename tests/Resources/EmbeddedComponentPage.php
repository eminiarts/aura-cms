<?php

namespace Aura\Base\Tests\Resources;

use Aura\Base\Fields\LivewireComponent;
use Aura\Base\Resource;

class EmbeddedComponentPage extends Resource
{
    public static ?string $slug = 'embedded-component-page';

    public static string $type = 'EmbeddedPage';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Embedded field',
                'slug' => 'embedded_field',
                'type' => LivewireComponent::class,
                'component_aliases' => [
                    'edit' => 'aura-tests.embedded-field',
                    'view' => 'aura-tests.embedded-field',
                    'index' => 'aura-tests.embedded-field',
                ],
                'on_forms' => true,
                'on_view' => true,
                'on_index' => true,
            ],
        ];
    }
}

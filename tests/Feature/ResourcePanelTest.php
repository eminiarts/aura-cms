<?php

use Aura\Base\Resource;

class ModelWithPanel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Tab 1',
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'style' => [],
            ],
            [
                'label' => 'Panel 1',
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-1',
                'style' => [],
            ],
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
            [
                'label' => 'Panel 2',
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-2',
                'style' => [],
            ],
            [
                'label' => 'other',
                'name' => 'other',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'other',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}

test('model groups fields with tabs containing panels', function () {
    $model = new ModelWithPanel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['name'])->toBe('Aura\Base\Fields\Tabs');
});

test('tab contains both panels as children', function () {
    $model = new ModelWithPanel;
    $fields = $model->getGroupedFields();

    $tab1 = $fields[0]['fields'][0];

    expect($tab1['name'])->toBe('Tab 1')
        ->and($tab1['slug'])->toBe('tab-1')
        ->and($tab1['fields'])->toHaveCount(2);
});

test('both panels are of Panel type', function () {
    $model = new ModelWithPanel;
    $fields = $model->getGroupedFields();

    $tab1Fields = $fields[0]['fields'][0]['fields'];

    expect($tab1Fields[0]['type'])->toBe('Aura\Base\Fields\Panel')
        ->and($tab1Fields[0]['name'])->toBe('Panel 1')
        ->and($tab1Fields[1]['type'])->toBe('Aura\Base\Fields\Panel')
        ->and($tab1Fields[1]['name'])->toBe('Panel 2');
});

test('each panel contains one text field', function () {
    $model = new ModelWithPanel;
    $fields = $model->getGroupedFields();

    $tab1Fields = $fields[0]['fields'][0]['fields'];

    expect($tab1Fields[0]['fields'])->toHaveCount(1)
        ->and($tab1Fields[0]['fields'][0]['slug'])->toBe('total')
        ->and($tab1Fields[1]['fields'])->toHaveCount(1)
        ->and($tab1Fields[1]['fields'][0]['slug'])->toBe('other');
});

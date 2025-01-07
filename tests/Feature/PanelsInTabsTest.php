<?php

use Aura\Base\Resource;

class PanelsInTabsModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
                'style' => [],
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel',
                'style' => [],
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-1-1',
            ],
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'panel-2',
                'style' => [],
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text-2-1',
            ],
        ];
    }
}

test('fields get grouped when field group is true', function () {
    $model = new PanelsInTabsModel;

    $fields = $model->getFieldsWithIds();

    $panel2 = $fields->firstWhere('slug', 'panel-2');

    expect($panel2)->toBeArray();
    expect($panel2['_parent_id'])->toBe(5);
});

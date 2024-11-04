<?php

use Aura\Base\Resource;

class ComplexFieldsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Panel',
                'slug' => 'panel',
                'global' => true,
                'type' => 'Aura\\Base\\Fields\\Panel',
                'conditional_logic' => [],
                'wrapper' => '',
            ],
            [
                'name' => 'Tab 1',
                'slug' => 'tab1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
            ],

            [
                'name' => 'Enabled',
                'slug' => 'enabled',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'style' => [
                    'width' => '50',
                ],
                'instructions' => 'Shows if it is enabled',
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
                'name' => 'Repeater',
                'slug' => 'repeater',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
            ],
            [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Aura\\Base\\Fields\\Text',
                'conditional_logic' => [],
                'slug' => 'description',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'number',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'label' => 'Number2',
                'name' => 'Number2',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'number2',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'label' => 'Number 3',
                'name' => 'Number 3',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'number3',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'name' => 'Tab 2',
                'slug' => 'tab2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
            ],
            [
                'name' => 'Text 2',
                'slug' => 'text2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
            ],
            [
                'name' => 'Enabled',
                'slug' => 'enabled2',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'style' => [
                    'width' => '50',
                ],
                'instructions' => 'Shows if it is enabled',
            ],
            [
                'name' => 'Panel 2',
                'slug' => 'panel1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
            ],
            [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Aura\\Base\\Fields\\Text',
                'conditional_logic' => [],
                'slug' => 'description3',
            ],
            [
                'label' => 'Image',
                'name' => 'Bild',
                'type' => 'Aura\\Base\\Fields\\Image',
                'conditional_logic' => [],
                'validation' => 'nullable',
                'wrapper' => [
                    'width' => '',
                    'class' => 'custom-image',
                    'id' => '',
                ],
                'slug' => 'bild',
            ],
            [
                'label' => 'Image',
                'name' => 'file',
                'type' => 'Aura\\Base\\Fields\\File',
                'conditional_logic' => [],
                'validation' => 'nullable',
                'wrapper' => [
                    'width' => '',
                    'class' => 'custom-image',
                    'id' => '',
                ],
                'slug' => 'file',
            ],
            [
                'name' => 'Text',
                'slug' => 'text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
            ],
        ];
    }
}

test('complex fields are possible and working correctly', function () {
    $model = new ComplexFieldsTestModel;

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['name'], 'Panel');
    $this->assertCount(1, $fields[0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Aura\Base\Fields\Tabs');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['name'], 'Tab 2');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][0]['name'], 'Enabled');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['fields'][0]['name'], 'Text 2');
});

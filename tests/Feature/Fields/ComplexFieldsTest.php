<?php

use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Models\Post;

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
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            [
                'name' => 'Tab 1',
                'slug' => 'tab1',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],

            [
                'name' => 'Enabled',
                'slug' => 'enabled',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'wrapper' => '',
                'style' => [
                    'width' => '50',
                ],
                'instructions' => 'Shows if it is enabled',
            ],
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [
                ],
                'slug' => 'total',
            ],
            [
                'name' => 'Repeater',
                'slug' => 'repeater',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'validation' => '',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'conditional_logic' => [
                ],
                'slug' => 'description',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'number',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'label' => 'Number2',
                'name' => 'Number2',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'number2',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'label' => 'Number 3',
                'name' => 'Number 3',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'slug' => 'number3',
                'style' => [
                    'width' => '33.3',
                ],
            ],
            [
                'name' => 'Tab 2',
                'slug' => 'tab2',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            [
                'name' => 'Text 2',
                'slug' => 'text2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            [
                'name' => 'Enabled',
                'slug' => 'enabled2',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => 'required',
                'conditional_logic' => [
                ],
                'wrapper' => '',
                'style' => [
                    'width' => '50',
                ],
                'instructions' => 'Shows if it is enabled',
            ],
            [
                'name' => 'Panel 2',
                'slug' => 'panel1',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'conditional_logic' => [
                ],
                'slug' => 'description3',
            ],
            [
                'label' => 'Image',
                'name' => 'Bild',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'conditional_logic' => [
                ],
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
                'type' => 'Eminiarts\\Aura\\Fields\\File',
                'conditional_logic' => [
                ],
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
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
        ];
    }
}

test('complex fields are possible and working correctly', function () {
    $model = new ComplexFieldsTestModel();

    $fields = $model->getGroupedFields();

    $this->assertCount(1, $fields);
    $this->assertEquals($fields[0]['name'], 'Panel');
    $this->assertCount(1, $fields[0]['fields']);
    $this->assertEquals($fields[0]['fields'][0]['name'], 'Tabs');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['name'], 'Tab 1');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['name'], 'Tab 2');
    $this->assertEquals($fields[0]['fields'][0]['fields'][0]['fields'][0]['name'], 'Enabled');
    $this->assertEquals($fields[0]['fields'][0]['fields'][1]['fields'][0]['name'], 'Text 2');
});

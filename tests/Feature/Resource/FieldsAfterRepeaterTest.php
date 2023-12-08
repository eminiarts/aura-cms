<?php

use Eminiarts\Aura\Resource;

// current
// uses()->group('current');

class FieldsAfterRepeaterModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Options',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
            ],
            [
                'name' => 'Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'name',
                'style' => [
                    'width' => '50',
                ],

            ],

            [
                'name' => 'Multiple',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => '',
                'exclude_from_nesting' => true,
                'slug' => 'multiple',
                'instructions' => 'Allow multiple selections?',
            ],
        ];
    }
}

test('multiple should be after the repeater', function () {
    $model = new FieldsAfterRepeaterModel();

    $fields = $model->createFields();
    ray()->clearScreen();
    ray($fields);

    // expect count to be 1
    expect(count($fields))->toBe(2);

    // $fields[0]['fields'] should have 1 field
    expect(count($fields[0]['fields']))->toBe(2);

    // slug should be tab-2
    expect($fields[1]['slug'])->toBe('multiple');
});

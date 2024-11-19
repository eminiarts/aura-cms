<?php

use Aura\Base\Resource;

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
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'name',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Multiple',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'exclude_level' => 1,
                'slug' => 'multiple',
                'instructions' => 'Allow multiple selections?',
            ],
        ];
    }
}

test('multiple should be after the repeater', function () {
    $model = new FieldsAfterRepeaterModel;

    $fields = $model->createFields();

    // expect count to be 1
    expect(count($fields))->toBe(2);

    // $fields[0]['fields'] should have 1 field
    expect(count($fields[0]['fields']))->toBe(2);

    // slug should be tab-2
    expect($fields[1]['slug'])->toBe('multiple');
});

<?php

use Aura\Base\Pipeline\AddIdsToFields;
use Aura\Base\Pipeline\ApplyParentConditionalLogic;
use Aura\Base\Pipeline\ApplyParentDisplayAttributes;
use Aura\Base\Pipeline\ApplyTabs;
use Aura\Base\Pipeline\FilterEditFields;
use Aura\Base\Pipeline\MapFields;
use Aura\Base\Resource;

// current
// uses()->group('current');

class EditFieldsTestModel extends Resource
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
                'on_edit' => false,
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'global' => true,
                'on_view' => false,
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('if the first tab is hidden, tabs should be applied correctly to second tab', function () {
    $model = new EditFieldsTestModel;

    $fields = $model->editFields();

    // expect count to be 1
    expect(count($fields))->toBe(1);

    // $fields[0]['fields'] should have 1 field
    expect(count($fields[0]['fields']))->toBe(1);

    // slug should be tab-2
    expect($fields[0]['fields'][0]['slug'])->toBe('tab-2');

    // $fields[0]['fields'][0]['fields'] should have 1 field
    expect(count($fields[0]['fields'][0]['fields']))->toBe(1);
});

test('check on_edit inheritance', function () {
    $model = new EditFieldsTestModel;

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
        ApplyParentDisplayAttributes::class,
        FilterEditFields::class,
    ]);

    // expect count to be 1
    expect(count($fields))->toBe(3);

    // tab-1 is not in the fields
    expect($fields->where('slug', 'tab-1')->count())->toBe(0);
    expect($fields->where('slug', 'text1')->count())->toBe(0);
    expect($fields->where('slug', 'tab-2')->count())->toBe(1);
    expect($fields->where('slug', 'text2')->count())->toBe(1);
});

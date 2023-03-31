<?php

use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\ApplyParentConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyParentDisplayAttributes;
use Eminiarts\Aura\Pipeline\ApplyTabs;
use Eminiarts\Aura\Pipeline\FilterCreateFields;
use Eminiarts\Aura\Pipeline\MapFields;
use Eminiarts\Aura\Resource;

// current
uses()->group('current');

class CreateFieldsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
                'on_create' => false,
            ],
            [
                'name' => 'Text 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'global' => true,
                'on_view' => false,
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('if the first tab is hidden, tabs should be applied correctly to second tab', function () {
    $model = new CreateFieldsTestModel();

    $fields = $model->createFields();

    // expect count to be 1
    expect(count($fields))->toBe(1);

    // $fields[0]['fields'] should have 1 field
    expect(count($fields[0]['fields']))->toBe(1);

    // slug should be tab-2
    expect($fields[0]['fields'][0]['slug'])->toBe('tab-2');

    // $fields[0]['fields'][0]['fields'] should have 1 field
    expect(count($fields[0]['fields'][0]['fields']))->toBe(1);
});

test('check on_create inheritance', function () {
    $model = new CreateFieldsTestModel();

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
        ApplyParentDisplayAttributes::class,
        FilterCreateFields::class,
    ]);

    // expect count to be 1
    expect(count($fields))->toBe(3);

    // tab-1 is not in the fields
    expect($fields->where('slug', 'tab-1')->count())->toBe(0);
    expect($fields->where('slug', 'text1')->count())->toBe(0);
    expect($fields->where('slug', 'tab-2')->count())->toBe(1);
    expect($fields->where('slug', 'text2')->count())->toBe(1);
});
